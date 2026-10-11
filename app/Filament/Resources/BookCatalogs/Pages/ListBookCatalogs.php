<?php

namespace App\Filament\Resources\BookCatalogs\Pages;

use App\Filament\Exports\BookCatalogExporter;
use App\Filament\Resources\BookCatalogs\BookCatalogResource;
use App\Models\BookCatalog;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\ExportAction;
use Filament\Actions\Exports\Enums\ExportFormat;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use OpenSpout\Reader\XLSX\Reader as XlsxReader;

class ListBookCatalogs extends ListRecords
{
    protected static string $resource = BookCatalogResource::class;

    /**
     * Column visibility survives logout/browser restart by storing it on
     * the user record instead of the session (which is wiped on logout).
     *
     * @return array<int, array<string, mixed>>
     */
    protected function loadTableColumnsFromSession(): array
    {
        return auth()->user()->book_catalogs_table_columns
            ?? $this->getDefaultTableColumnState();
    }

    protected function persistTableColumns(): void
    {
        if (! $this->getTable()->persistsColumnsInSession()) {
            return;
        }

        auth()->user()->update([
            'book_catalogs_table_columns' => $this->tableColumns,
        ]);
    }

    /**
     * An inline edit only changes that one cell, which the browser already
     * shows, so skip re-rendering the whole (wide) table. Category/publisher
     * still re-render because the ✓ columns and filters depend on them.
     */
    public function updateTableColumnState(string $column, string $record, mixed $input): mixed
    {
        $result = parent::updateTableColumnState($column, $record, $input);

        if (! in_array($column, ['category', 'publisher'], true) && ! isset($result['error'])) {
            $this->skipRender();
        }

        return $result;
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label(app()->getLocale() === 'ar' ? 'إضافة كتاب' : 'Add Book'),

            ExportAction::make('exportExcel')
                ->label(app()->getLocale() === 'ar' ? 'تصدير Excel' : 'Export Excel')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->exporter(BookCatalogExporter::class)
                ->formats([
                    ExportFormat::Xlsx,
                ])
                ->enableVisibleTableColumnsByDefault()
                ->columnMappingColumns(3)
                ->modifyQueryUsing(function (Builder $query, array $options): Builder {
                    if (($options['export_scope'] ?? 'all') !== 'range') {
                        return $query;
                    }

                    $from = $options['catalog_number_from'] ?? null;
                    $to = $options['catalog_number_to'] ?? null;

                    if (filled($from)) {
                        $query->where('catalog_number', '>=', (int) $from);
                    }

                    if (filled($to)) {
                        $query->where('catalog_number', '<=', (int) $to);
                    }

                    return $query;
                })
                ->maxRows(10000),

            Action::make('importExcel')
                ->label(app()->getLocale() === 'ar' ? 'استيراد التغييرات' : 'Import changes')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('info')
                ->schema([
                    FileUpload::make('file')
                        ->label(app()->getLocale() === 'ar' ? 'ملف Excel المعدّل' : 'Edited Excel file')
                        ->helperText(app()->getLocale() === 'ar'
                            ? 'ملف مصدَّر من هذه الصفحة، بعد إجراء التعديلات عليه. المطابقة تتم عبر عمود "باركد الكتاب".'
                            : 'A file exported from this page, after you\'ve edited it. Rows are matched by the barcode column.')
                        ->acceptedFileTypes(['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'])
                        ->rules(['mimes:xlsx'])
                        ->storeFiles(false)
                        ->required(),
                ])
                ->action(function (array $data): void {
                    $file = $data['file'];

                    $result = $file instanceof TemporaryUploadedFile
                        ? static::syncBookCatalogsFromExcel($file->getRealPath())
                        : ['updated' => 0, 'notFound' => 0, 'duplicates' => 0, 'ambiguous' => 0, 'numberTaken' => 0, 'noBarcode' => true];

                    $ar = app()->getLocale() === 'ar';

                    if ($result['noBarcode']) {
                        Notification::make()
                            ->title($ar ? 'تعذر الاستيراد' : 'Import failed')
                            ->body($ar
                                ? 'لم يتم العثور على عمود "باركد الكتاب" في الملف، وهو مطلوب لمطابقة السجلات.'
                                : 'No barcode column was found in the file, which is required to match records.')
                            ->danger()
                            ->send();

                        return;
                    }

                    $body = $ar
                        ? "تم تحديث {$result['updated']} سجل. لم يتم العثور على {$result['notFound']} باركد."
                        : "{$result['updated']} records updated. {$result['notFound']} barcodes were not found.";

                    if ($result['duplicates'] > 0) {
                        $body .= $ar
                            ? " تم تجاهل {$result['duplicates']} صف مكرر لنفس الباركد (تم اعتماد أول صف فقط)."
                            : " {$result['duplicates']} rows had a repeated barcode and were skipped (only the first was applied).";
                    }

                    if ($result['ambiguous'] > 0) {
                        $body .= $ar
                            ? " تم تجاهل {$result['ambiguous']} باركد لأنه مسجل لأكثر من كتاب."
                            : " {$result['ambiguous']} barcodes belong to more than one book and were skipped.";
                    }

                    if ($result['numberTaken'] > 0) {
                        $body .= $ar
                            ? " لم يتم تغيير \"الرقم\" في {$result['numberTaken']} سجل لأنه مستخدم لكتاب آخر."
                            : " {$result['numberTaken']} catalog numbers were left unchanged because another book already uses them.";
                    }

                    Notification::make()
                        ->title($ar ? 'تم الاستيراد' : 'Import complete')
                        ->body($body)
                        ->success()
                        ->send();
                }),
        ];
    }

    /**
     * Reads back a file previously produced by BookCatalogExporter (brand
     * row, then a header row, then data) and updates matching records by
     * barcode. Column set/order follows whatever the export's header
     * row says, so it still works after column mapping or reordering.
     *
     * ponytail: single-threaded row-by-row save, fine at this table's size
     * (thousands, not millions); batch-upsert if that ever changes.
     *
     * @return array{updated: int, notFound: int, duplicates: int, ambiguous: int, numberTaken: int, noBarcode: bool}
     */
    protected static function syncBookCatalogsFromExcel(string $path): array
    {
        $fieldsByLabel = collect(BookCatalogExporter::getColumns())
            ->mapWithKeys(fn ($column) => [$column->getLabel() => $column->getName()]);

        $categoryKeysByLabel = array_flip(BookCatalog::categoryOptions());
        $publisherKeysByLabel = array_flip(BookCatalog::publisherOptions());

        $reader = new XlsxReader();
        $reader->open($path);

        $headerMap = [];
        $barcodeColumn = null;
        $updated = 0;
        $notFound = 0;
        $duplicates = 0;
        $ambiguous = 0;
        $numberTaken = 0;
        $seenBarcodes = [];
        $rowNumber = 0;

        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $row) {
                $rowNumber++;

                if ($rowNumber === 1) {
                    continue; // brand title row
                }

                $cells = $row->toArray();

                if ($rowNumber === 2) {
                    foreach ($cells as $index => $label) {
                        if ($field = $fieldsByLabel->get(trim((string) $label))) {
                            $headerMap[$index] = $field;

                            if ($field === 'barcode') {
                                $barcodeColumn = $index;
                            }
                        }
                    }

                    continue;
                }

                if ($barcodeColumn === null) {
                    break 2;
                }

                $barcode = static::normalizeBarcode($cells[$barcodeColumn] ?? null);

                if ($barcode === '') {
                    continue;
                }

                if (isset($seenBarcodes[$barcode])) {
                    // Same barcode twice in the file: first occurrence wins,
                    // this one is reported instead of silently overwriting it.
                    $duplicates++;

                    continue;
                }

                $seenBarcodes[$barcode] = true;

                $matches = BookCatalog::where('barcode', $barcode)->limit(2)->get();

                if ($matches->isEmpty()) {
                    $notFound++;

                    continue;
                }

                if ($matches->count() > 1) {
                    // barcode isn't unique in the DB; guessing which book is
                    // meant could overwrite the wrong one, so skip and report.
                    $ambiguous++;

                    continue;
                }

                $record = $matches->first();

                $values = [];

                foreach ($headerMap as $index => $field) {
                    if ($field === 'barcode') {
                        continue; // matching key, not editable via import
                    }

                    $value = $cells[$index] ?? null;
                    $value = is_string($value) ? trim($value) : $value;
                    $value = $value === '' ? null : $value;

                    if ($field === 'quantity') {
                        $value = (int) $value; // NOT NULL column
                    }

                    if ($field === 'category' && $value !== null) {
                        $value = $categoryKeysByLabel[$value] ?? $value;
                    }

                    if ($field === 'publisher' && $value !== null) {
                        $value = $publisherKeysByLabel[$value] ?? $value;
                    }

                    $values[$field] = $value;
                }

                if (array_key_exists('catalog_number', $values)) {
                    $number = $values['catalog_number'];

                    // Required + unique: keep the current one when the cell is
                    // empty, or when another book already has that number.
                    if ($number === null || (int) $number === $record->catalog_number) {
                        unset($values['catalog_number']);
                    } elseif (BookCatalog::where('catalog_number', (int) $number)->exists()) {
                        unset($values['catalog_number']);
                        $numberTaken++;
                    } else {
                        $values['catalog_number'] = (int) $number;
                    }
                }

                $record->fill($values)->save();
                $updated++;
            }

            break; // only the first sheet
        }

        $reader->close();

        return [
            'updated' => $updated,
            'notFound' => $notFound,
            'duplicates' => $duplicates,
            'ambiguous' => $ambiguous,
            'numberTaken' => $numberTaken,
            'noBarcode' => $barcodeColumn === null,
        ];
    }

    /** Excel may hand a barcode back as a number (9789642504435.0); compare as plain digits text. */
    protected static function normalizeBarcode(mixed $value): string
    {
        if (is_float($value)) {
            return sprintf('%.0f', $value);
        }

        return trim((string) $value);
    }
}
