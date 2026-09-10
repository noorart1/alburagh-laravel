<?php

namespace App\Filament\Exports;

use App\Models\BookCatalog;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use OpenSpout\Common\Entity\Style\CellAlignment;
use OpenSpout\Common\Entity\Style\CellVerticalAlignment;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Entity\SheetView;
use OpenSpout\Writer\XLSX\Writer;

class BookCatalogExporter extends Exporter
{
    protected static ?string $model = BookCatalog::class;

    public static function getOptionsFormComponents(): array
    {
        $ar = app()->getLocale() === 'ar';

        return [
            Select::make('export_scope')
                ->label($ar ? 'السجلات المطلوب تصديرها' : 'Rows to export')
                ->options([
                    'all' => $ar ? 'كل الكتب' : 'All books',
                    'range' => $ar ? 'من رقم # إلى رقم #' : 'Catalog # range',
                ])
                ->default('all')
                ->required(),

            TextInput::make('catalog_number_from')
                ->label($ar ? 'من #' : 'From #')
                ->numeric()
                ->minValue(1000)
                ->placeholder('1000'),

            TextInput::make('catalog_number_to')
                ->label($ar ? 'إلى #' : 'To #')
                ->numeric()
                ->minValue(1000)
                ->placeholder('1523'),
        ];
    }

    public static function getColumns(): array
    {
        $ar = app()->getLocale() === 'ar';

        return [
            ExportColumn::make('catalog_number')->label('#'),
            ExportColumn::make('barcode')->label($ar ? 'باركد الكتاب' : 'Barcode'),
            ExportColumn::make('series_name')->label($ar ? 'اسم السلسلة' : 'Series Name'),
            ExportColumn::make('title')->label($ar ? 'اسم الكتاب' : 'Book Title'),
            ExportColumn::make('author')->label($ar ? 'اسم المؤلف' : 'Author'),
            ExportColumn::make('illustrator')->label($ar ? 'الرسام' : 'Illustrator'),
            ExportColumn::make('subject')->label($ar ? 'موضوع الكتاب' : 'Subject'),
            ExportColumn::make('edition_number')->label($ar ? 'رقم الطبعة' : 'Edition'),
            ExportColumn::make('print_place')->label($ar ? 'مكان الطباعة' : 'Print Place'),
            ExportColumn::make('print_year')->label($ar ? 'سنة الطباعة' : 'Print Year'),
            ExportColumn::make('short_description')->label($ar ? 'نبذة مختصرة' : 'Short Description'),
            ExportColumn::make('pages')->label($ar ? 'عدد الصفحات' : 'Pages'),
            ExportColumn::make('price_usd')->label($ar ? 'دولار' : 'USD'),
            ExportColumn::make('price_aed')->label($ar ? 'درهم' : 'AED'),
            ExportColumn::make('price_iqd')->label($ar ? 'دينار' : 'IQD'),
            ExportColumn::make('deposit_number')->label($ar ? 'رقم الإيداع' : 'Deposit No.'),
            ExportColumn::make('deposit_year')->label($ar ? 'سنة الإيداع' : 'Deposit Year'),
            ExportColumn::make('size')->label($ar ? 'القياس' : 'Size'),
            ExportColumn::make('weight')->label($ar ? 'الوزن' : 'Weight'),
            ExportColumn::make('age_group')->label($ar ? 'الفئة العمرية' : 'Age Group'),
        ];
    }

    public function getXlsxCellStyle(): ?Style
    {
        return (new Style())
            ->setCellAlignment(CellAlignment::RIGHT)
            ->setCellVerticalAlignment(CellVerticalAlignment::CENTER);
    }

    public function getXlsxHeaderCellStyle(): ?Style
    {
        return (new Style())
            ->setFontBold()
            ->setCellAlignment(CellAlignment::RIGHT)
            ->setCellVerticalAlignment(CellVerticalAlignment::CENTER);
    }

    public function configureXlsxWriterBeforeClose(Writer $writer): Writer
    {
        $sheetView = new SheetView();
        $sheetView->setRightToLeft(true);

        $sheet = $writer->getCurrentSheet();
        $sheet->setSheetView($sheetView);
        $sheet->setName('الكتب');

        return $writer;
    }

    public static function getCompletedNotificationTitle(Export $export): string
    {
        return app()->getLocale() === 'ar'
            ? 'تم تجهيز ملف Excel'
            : 'Excel export is ready';
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        return app()->getLocale() === 'ar'
            ? "{$export->successful_rows} سجل تم تصديره بنجاح."
            : "{$export->successful_rows} records exported successfully.";
    }
}
