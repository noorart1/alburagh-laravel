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
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
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
        $invoices->loadMissing('items');
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

        // Words, not "a - b": a dash range renders reversed in a right-to-left sheet.
        $dates = $first->date->equalTo($last->date)
            ? $first->date->format('Y-m-d')
            : 'من '.$first->date->format('Y-m-d').' إلى '.$last->date->format('Y-m-d');

        $brand = 'D97706'; // panel primary (amber 600), same as the book catalog export
        $border = ['borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'BFBFBF']]]];
        $spreadsheet->getDefaultStyle()->getFont()->setName('Arial')->setSize(11);
        $spreadsheet->getDefaultStyle()->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);

        // Row 1: title band
        $sheet->setCellValue('A1', ($combined ? 'فاتورة مجمّعة' : 'فاتورة').' - دار البراق لثقافة الأطفال');
        $sheet->mergeCells('A1:F1');
        $sheet->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 15, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $brand]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(32);

        // Rows 2-5: label (A:B) | value (C:F)
        $info = [
            'اسم المشتري' => $first->buyer_name,
            'التاريخ' => $dates,
            'العملة' => Invoice::currencyOptions()[$first->currency] ?? $first->currency,
            ($combined ? 'أرقام الفواتير' : 'رقم الفاتورة') => $invoices->pluck('id')->implode('، '),
        ];
        $row = 2;
        foreach ($info as $label => $value) {
            $sheet->setCellValue("A{$row}", $label);
            $sheet->setCellValueExplicit("C{$row}", (string) $value, DataType::TYPE_STRING);
            $sheet->mergeCells("A{$row}:B{$row}");
            $sheet->mergeCells("C{$row}:F{$row}");
            $sheet->getStyle("A{$row}:F{$row}")->applyFromArray($border);
            $sheet->getStyle("C{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle("A{$row}")->applyFromArray([
                'font' => ['bold' => true],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F2F2F2']],
            ]);
            $sheet->getRowDimension($row)->setRowHeight(20);
            $row++;
        }

        // Row 6: table header
        $sheet->fromArray(['ت', 'باركود', 'اسم الكتاب', 'السعر', 'الكمية', 'المجموع'], null, 'A6');
        $sheet->getStyle('A6:F6')->applyFromArray($border + [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $brand]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        $sheet->getRowDimension(6)->setRowHeight(22);

        $row = 7;
        foreach ($items as $item) {
            $sheet->setCellValue("A{$row}", $row - 6); // row number 1, 2, 3…
            // Explicit string so long barcodes don't turn into 9.78E+12.
            $sheet->setCellValueExplicit("B{$row}", (string) $item['barcode'], DataType::TYPE_STRING);
            $sheet->setCellValue("C{$row}", $item['title']);
            $sheet->setCellValue("D{$row}", $item['unit_price']);
            $sheet->setCellValue("E{$row}", (int) $item['quantity']);
            $sheet->setCellValue("F{$row}", "=D{$row}*E{$row}");
            $sheet->getRowDimension($row)->setRowHeight(20);

            if ($row % 2 === 0) {
                $sheet->getStyle("A{$row}:F{$row}")->getFill()
                    ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FFF7ED');
            }

            $row++;
        }

        if ($row > 7) {
            $sheet->getStyle('A7:F'.($row - 1))->applyFromArray($border);
            $sheet->getStyle('A7:B'.($row - 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('D7:F'.($row - 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }

        // Total row
        $sheet->setCellValue("A{$row}", 'الإجمالي');
        $sheet->mergeCells("A{$row}:E{$row}");
        $sheet->setCellValue("F{$row}", $row > 7 ? '=SUM(F7:F'.($row - 1).')' : 0);
        $sheet->getStyle("A{$row}:F{$row}")->applyFromArray($border + [
            'font' => ['bold' => true, 'size' => 12],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FEF3C7']],
        ]);
        $sheet->getStyle("A{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle("F{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getRowDimension($row)->setRowHeight(24);

        // Dinar amounts are whole numbers; USD/AED keep cents.
        $format = in_array($first->currency, ['usd', 'aed'], true) ? '#,##0.00' : '#,##0';
        $sheet->getStyle("D7:D{$row}")->getNumberFormat()->setFormatCode($format);
        $sheet->getStyle("F7:F{$row}")->getNumberFormat()->setFormatCode($format);

        foreach (['A' => 6, 'B' => 18, 'C' => 45, 'D' => 12, 'E' => 10, 'F' => 15] as $column => $width) {
            $sheet->getColumnDimension($column)->setWidth($width);
        }

        $sheet->getPageSetup()
            ->setPaperSize(PageSetup::PAPERSIZE_A4)
            ->setFitToWidth(1)
            ->setFitToHeight(0);
        $sheet->setSelectedCell('A1');

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
