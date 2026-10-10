<?php

namespace App\Filament\Resources\Invoices;

use App\Filament\Resources\Invoices\Pages\CreateInvoice;
use App\Filament\Resources\Invoices\Pages\EditInvoice;
use App\Filament\Resources\Invoices\Pages\ListInvoices;
use App\Filament\Resources\Invoices\Schemas\InvoiceForm;
use App\Filament\Resources\Invoices\Tables\InvoicesTable;
use App\Models\Invoice;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InvoiceResource extends Resource
{
    protected static ?string $model = Invoice::class;

    protected static ?int $navigationSort = 21;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    public static function getNavigationLabel(): string
    {
        return app()->getLocale() === 'ar' ? 'الفواتير' : 'Invoices';
    }

    public static function getModelLabel(): string
    {
        return app()->getLocale() === 'ar' ? 'فاتورة' : 'Invoice';
    }

    public static function getPluralModelLabel(): string
    {
        return app()->getLocale() === 'ar' ? 'الفواتير' : 'Invoices';
    }

    public static function getNavigationGroup(): ?string
    {
        return app()->getLocale() === 'ar' ? 'إدارة الكتب' : 'Book Management';
    }

    public static function form(Schema $schema): Schema
    {
        return InvoiceForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return InvoicesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListInvoices::route('/'),
            'create' => CreateInvoice::route('/create'),
            'edit' => EditInvoice::route('/{record}/edit'),
        ];
    }

    /**
     * One invoice, or several of one buyer combined into a single sheet
     * (same book at the same price becomes one line with summed quantity).
     *
     * @param  Invoice|Collection<int, Invoice>  $invoices
     */
    public static function downloadExcel(Invoice|Collection $invoices): StreamedResponse
    {
        $invoices = Collection::wrap($invoices)->sortBy([['date', 'asc'], ['id', 'asc']])->values();
        $invoices->load('items');
        $first = $invoices->first();
        $last = $invoices->last();
        $combined = $invoices->count() > 1;

        $items = $invoices->flatMap->items
            ->groupBy(fn ($item) => $item->barcode.'|'.$item->title.'|'.$item->unit_price)
            ->map(fn ($group) => [
                'barcode' => $group->first()->barcode,
                'title' => $group->first()->title,
                'unit_price' => (float) $group->first()->unit_price,
                'quantity' => $group->sum('quantity'),
            ]);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('فاتورة');
        $sheet->setRightToLeft(true);

        $dates = $first->date->format('Y-m-d');
        if (! $first->date->equalTo($last->date)) {
            $dates .= ' - '.$last->date->format('Y-m-d');
        }

        $sheet->fromArray([
            [($combined ? 'فاتورة مجمّعة' : 'فاتورة').' - دار البراق لثقافة الأطفال'],
            ['اسم المشتري', $first->buyer_name],
            ['التاريخ', $dates],
            ['العملة', Invoice::currencyOptions()[$first->currency] ?? $first->currency],
            [$combined ? 'أرقام الفواتير' : 'رقم الفاتورة', $invoices->pluck('id')->implode('، ')],
            ['باركود', 'اسم الكتاب', 'السعر', 'الكمية', 'المجموع'],
        ]);
        $sheet->mergeCells('A1:E1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A2:A5')->getFont()->setBold(true);
        $sheet->getStyle('A6:E6')->getFont()->setBold(true);

        $row = 7;
        foreach ($items as $item) {
            // Explicit string so long barcodes don't turn into 9.78E+12.
            $sheet->setCellValueExplicit("A{$row}", (string) $item['barcode'], DataType::TYPE_STRING);
            $sheet->setCellValue("B{$row}", $item['title']);
            $sheet->setCellValue("C{$row}", $item['unit_price']);
            $sheet->setCellValue("D{$row}", (int) $item['quantity']);
            $sheet->setCellValue("E{$row}", "=C{$row}*D{$row}");
            $row++;
        }

        $sheet->setCellValue("D{$row}", 'الإجمالي');
        $sheet->setCellValue("E{$row}", $row > 7 ? '=SUM(E7:E'.($row - 1).')' : 0);
        $sheet->getStyle("D{$row}:E{$row}")->getFont()->setBold(true);
        // Dinar amounts are whole numbers; USD/AED keep cents.
        $format = in_array($first->currency, ['usd', 'aed'], true) ? '#,##0.00' : '#,##0';
        $sheet->getStyle("C7:C{$row}")->getNumberFormat()->setFormatCode($format);
        $sheet->getStyle("E7:E{$row}")->getNumberFormat()->setFormatCode($format);

        foreach (['A' => 18, 'B' => 45, 'C' => 12, 'D' => 10, 'E' => 14] as $column => $width) {
            $sheet->getColumnDimension($column)->setWidth($width);
        }

        $filename = $combined
            ? 'invoices-'.Str::slug($first->buyer_name, '-', null).'-'.$last->date->format('Y-m-d').'.xlsx'
            : 'invoice-'.$first->id.'-'.$first->date->format('Y-m-d').'.xlsx';

        return response()->streamDownload(
            fn () => (new Xlsx($spreadsheet))->save('php://output'),
            $filename,
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
        );
    }
}
