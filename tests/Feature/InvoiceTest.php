<?php

namespace Tests\Feature;

use App\Filament\Resources\BookCatalogs\Pages\EditBookCatalog;
use App\Filament\Resources\BookCatalogs\Pages\ListBookCatalogs;
use App\Filament\Resources\BookCatalogs\Pages\ViewBookCatalog;
use App\Filament\Resources\Invoices\InvoiceResource;
use App\Filament\Resources\Invoices\Pages\CreateInvoice;
use App\Filament\Resources\Invoices\Pages\ListInvoices;
use App\Models\BookCatalog;
use App\Models\Invoice;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PhpOffice\PhpSpreadsheet\IOFactory;
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

        Livewire::test(ListInvoices::class)
            ->selectTableRecords([$first->id, $second->id])
            ->callAction(TestAction::make('merge')->table()->bulk());

        $merged = Invoice::sole();
        $this->assertSame($first->id, $merged->id);
        $this->assertEquals(17000, $merged->total);
        $this->assertCount(2, $merged->items);

        $response = InvoiceResource::downloadExcel($merged);
        ob_start();
        $response->sendContent();
        $path = tempnam(sys_get_temp_dir(), 'inv').'.xlsx';
        file_put_contents($path, ob_get_clean());

        $sheet = IOFactory::load($path)->getActiveSheet();
        $this->assertSame('9786030000001', $sheet->getCell('A7')->getValue());
        $this->assertEquals(17000, $sheet->getCell('E9')->getCalculatedValue());
    }
}
