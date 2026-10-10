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

    public static function downloadExcel(Invoice $invoice): StreamedResponse
    {
        $invoice->loadMissing('items');

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('فاتورة');
        $sheet->setRightToLeft(true);

        $sheet->fromArray([
            ['فاتورة - دار البراق لثقافة الأطفال'],
            ['اسم المشتري', $invoice->buyer_name],
            ['التاريخ', $invoice->date->format('Y-m-d')],
            ['العملة', Invoice::currencyOptions()[$invoice->currency] ?? $invoice->currency],
            [],
            ['باركود', 'اسم الكتاب', 'السعر', 'الكمية', 'المجموع'],
        ]);
        $sheet->mergeCells('A1:E1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A2:A4')->getFont()->setBold(true);
        $sheet->getStyle('A6:E6')->getFont()->setBold(true);

        $row = 7;
        foreach ($invoice->items as $item) {
            // Explicit string so long barcodes don't turn into 9.78E+12.
            $sheet->setCellValueExplicit("A{$row}", (string) $item->barcode, DataType::TYPE_STRING);
            $sheet->setCellValue("B{$row}", $item->title);
            $sheet->setCellValue("C{$row}", (float) $item->unit_price);
            $sheet->setCellValue("D{$row}", (int) $item->quantity);
            $sheet->setCellValue("E{$row}", "=C{$row}*D{$row}");
            $row++;
        }

        $sheet->setCellValue("D{$row}", 'الإجمالي');
        $sheet->setCellValue("E{$row}", $row > 7 ? '=SUM(E7:E'.($row - 1).')' : 0);
        $sheet->getStyle("D{$row}:E{$row}")->getFont()->setBold(true);
        $sheet->getStyle("C7:C{$row}")->getNumberFormat()->setFormatCode('#,##0.##');
        $sheet->getStyle("E7:E{$row}")->getNumberFormat()->setFormatCode('#,##0.##');

        foreach (['A' => 18, 'B' => 45, 'C' => 12, 'D' => 10, 'E' => 14] as $column => $width) {
            $sheet->getColumnDimension($column)->setWidth($width);
        }

        $filename = 'invoice-'.$invoice->id.'-'.$invoice->date->format('Y-m-d').'.xlsx';

        return response()->streamDownload(
            fn () => (new Xlsx($spreadsheet))->save('php://output'),
            $filename,
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
        );
    }
}
