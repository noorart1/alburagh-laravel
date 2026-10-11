<?php

namespace Tests\Feature;

use App\Filament\Resources\BookCatalogs\Pages\EditBookCatalog;
use App\Filament\Resources\BookCatalogs\Pages\ListBookCatalogs;
use App\Filament\Resources\BookCatalogs\Pages\ViewBookCatalog;
use App\Filament\Resources\Invoices\InvoiceResource;
use App\Filament\Resources\Invoices\Pages\CreateInvoice;
use App\Filament\Resources\Invoices\Pages\EditInvoice;
use App\Filament\Resources\Invoices\Pages\ListInvoices;
use App\Models\BookCatalog;
use App\Models\Invoice;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Tests\TestCase;

class InvoiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_book_pages_render_and_quantity_edits_inline(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true, 'is_active' => true]));
        $book = BookCatalog::create(['catalog_number' => 5, 'title' => 'كتاب']);

        BookCatalog::create(['catalog_number' => 6, 'title' => 'آخر']);

        Livewire::test(ListBookCatalogs::class)
            ->loadTable()
            ->call('updateTableColumnState', 'quantity', (string) $book->getKey(), 7)
            ->call('updateTableColumnState', 'title', (string) $book->getKey(), 'عنوان جديد')
            ->call('updateTableColumnState', 'category', (string) $book->getKey(), 'game')
            ->call('updateTableColumnState', 'price_usd', (string) $book->getKey(), '')
            ->call('updateTableColumnState', 'catalog_number', (string) $book->getKey(), 6); // duplicate → rejected

        $book->refresh();
        $this->assertSame(7, $book->quantity);
        $this->assertSame('عنوان جديد', $book->title);
        $this->assertSame('game', $book->category);
        $this->assertNull($book->price_usd);
        $this->assertSame(5, $book->catalog_number);

        Livewire::test(EditBookCatalog::class, ['record' => $book->getKey()])->assertOk();
        Livewire::test(ViewBookCatalog::class, ['record' => $book->getKey()])->assertOk();
        $this->get(InvoiceResource::getUrl('index'))->assertOk();
        $this->get(InvoiceResource::getUrl('create'))->assertOk();
    }

    public function test_create_merge_and_export(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true, 'is_active' => true]));

        $book = BookCatalog::create([
            'catalog_number' => 1, 'title' => 'كتاب', 'barcode' => '9786030000001',
            'price_iqd' => 5000, 'price_usd' => 4, 'cost_iqd' => 3000,
        ]);

        // Picking a book fills barcode/title/price from the chosen currency.
        Livewire::test(CreateInvoice::class)
            ->fillForm(['buyer_name' => 'علي', 'currency' => 'iqd', 'items' => []])
            ->set('data.items', ['a' => ['book_catalog_id' => null, 'quantity' => 3]])
            ->fillForm(['items.a.book_catalog_id' => $book->id])
            ->assertSchemaStateSet(['items.a.unit_price' => 5000, 'items.a.title' => 'كتاب'])
            ->fillForm(['currency' => 'cost_iqd'])
            ->assertSchemaStateSet(['items.a.unit_price' => 3000])
            ->fillForm(['currency' => 'iqd'])
            ->call('create')
            ->assertHasNoFormErrors();

        $first = Invoice::sole();
        $this->assertEquals(15000, $first->total);

        $second = Invoice::create(['buyer_name' => 'علي', 'date' => now(), 'currency' => 'iqd']);
        $second->items()->create(['title' => 'آخر', 'unit_price' => 1000, 'quantity' => 2]);

        // Combined export without merging: both invoices stay, one sheet, totals add up.
        $second->items()->create(['barcode' => '9786030000001', 'title' => 'كتاب', 'unit_price' => 5000, 'quantity' => 1]);
        $combined = InvoiceResource::downloadExcel(Invoice::all());
        $sheet = $this->sheetFrom($combined);
        $this->assertSame('9786030000001', $sheet->getCell('B7')->getValue());
        $this->assertEquals(4, $sheet->getCell('E7')->getValue()); // 3 + 1 of the same book merged into one line
        $this->assertEquals(22000, $sheet->getCell('F9')->getCalculatedValue());
        $this->assertSame(2, Invoice::count());
        $second->items()->where('title', 'كتاب')->delete();

        Livewire::test(ListInvoices::class)
            ->callAction('buyerCombined', ['buyer_name' => 'علي', 'currency' => 'iqd', 'merge' => false])
            ->assertHasNoActionErrors();
        $this->assertSame(2, Invoice::count());

        Livewire::test(ListInvoices::class)
            ->selectTableRecords([$first->id, $second->id])
            ->callAction(TestAction::make('merge')->table()->bulk());

        $merged = Invoice::sole();
        $this->assertSame($first->id, $merged->id);
        $this->assertEquals(17000, $merged->total);
        $this->assertCount(2, $merged->items);

        $sheet = $this->sheetFrom(InvoiceResource::downloadExcel($merged));
        $this->assertSame('9786030000001', $sheet->getCell('B7')->getValue());
        $this->assertEquals(17000, $sheet->getCell('F9')->getCalculatedValue());
        $this->assertEquals([1, 2], [$sheet->getCell('A7')->getValue(), $sheet->getCell('A8')->getValue()]);
    }

    private function sheetFrom(StreamedResponse $response): Worksheet
    {
        ob_start();
        $response->sendContent();
        $path = tempnam(sys_get_temp_dir(), 'inv').'.xlsx';
        file_put_contents($path, ob_get_clean());

        return IOFactory::load($path)->getActiveSheet();
    }

    public function test_excel_import_matches_by_barcode(): void
    {
        $a = BookCatalog::create(['catalog_number' => 1, 'barcode' => '9789642504435', 'title' => 'A']);
        $b = BookCatalog::create(['catalog_number' => 2, 'barcode' => '111', 'title' => 'B']);
        BookCatalog::create(['catalog_number' => 3, 'barcode' => '222', 'title' => 'C1']);
        BookCatalog::create(['catalog_number' => 4, 'barcode' => '222', 'title' => 'C2']);

        $sheet = (new Spreadsheet())->getActiveSheet();
        $sheet->fromArray([
            ['brand'],
            ['Barcode', 'الرقم', 'Book Title'],
            [9789642504435, 10, 'A new'],  // numeric barcode, number changed
            ['111', 3, 'B new'],           // number 3 is taken by C1 → kept
            ['222', null, 'ambiguous'],    // barcode on two books → skipped
            ['999', null, 'missing'],
            ['111', null, 'dup'],          // repeated barcode → skipped
        ]);
        $path = tempnam(sys_get_temp_dir(), 'imp').'.xlsx';
        (new Xlsx($sheet->getParent()))->save($path);

        $sync = new \ReflectionMethod(ListBookCatalogs::class, 'syncBookCatalogsFromExcel');
        $result = $sync->invoke(null, $path);

        $this->assertSame(['updated' => 2, 'notFound' => 1, 'duplicates' => 1, 'ambiguous' => 1, 'numberTaken' => 1, 'noBarcode' => false], $result);
        $this->assertSame([10, 'A new'], [$a->refresh()->catalog_number, $a->title]);
        $this->assertSame([2, 'B new'], [$b->refresh()->catalog_number, $b->title]);
        $this->assertSame(0, BookCatalog::where('title', 'ambiguous')->count());
    }

    public function test_invoice_lines_move_book_stock(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true, 'is_active' => true]));
        $book = BookCatalog::create(['catalog_number' => 1, 'title' => 'A', 'price_iqd' => 1000, 'quantity' => 10]);
        $other = BookCatalog::create(['catalog_number' => 2, 'title' => 'B', 'price_iqd' => 1000, 'quantity' => 5]);

        Livewire::test(CreateInvoice::class)
            ->fillForm(['buyer_name' => 'علي', 'currency' => 'iqd', 'items' => []])
            ->set('data.items', ['a' => ['book_catalog_id' => null, 'quantity' => 3], 'b' => ['book_catalog_id' => null, 'quantity' => 2]])
            ->fillForm(['items.a.book_catalog_id' => $book->id, 'items.b.book_catalog_id' => $other->id])
            ->call('create')
            ->assertHasNoFormErrors();
        $this->assertSame([7, 3], [$book->refresh()->quantity, $other->refresh()->quantity]);

        $invoice = Invoice::sole();
        [$lineA, $lineB] = $invoice->items->sortBy('id')->values()->all();
        Livewire::test(EditInvoice::class, ['record' => $invoice->getKey()])
            ->set('data.items', [
                "record-{$lineA->id}" => ['book_catalog_id' => $book->id, 'barcode' => null, 'title' => 'A', 'unit_price' => 1000, 'quantity' => 12],
            ]) // A: 3 → 12 (stock goes negative), B removed
            ->call('save')
            ->assertHasNoFormErrors();
        $this->assertSame([-2, 5], [$book->refresh()->quantity, $other->refresh()->quantity]);

        $invoice->delete();
        $this->assertSame([10, 5], [$book->refresh()->quantity, $other->refresh()->quantity]);
    }
}
