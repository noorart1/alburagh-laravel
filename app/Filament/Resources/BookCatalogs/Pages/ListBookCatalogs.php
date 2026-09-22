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
                            ? 'ملف مصدَّر من هذه الصفحة، بعد إجراء التعديلات عليه. المطابقة تتم عبر عمود "الرقم".'
                            : 'A file exported from this page, after you\'ve edited it. Rows are matched by the catalog number column.')
                        ->acceptedFileTypes(['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'])
                        ->rules(['mimes:xlsx'])
                        ->storeFiles(false)
                        ->required(),
                ])
                ->action(function (array $data): void {
                    $file = $data['file'];

                    $result = $file instanceof TemporaryUploadedFile
                        ? static::syncBookCatalogsFromExcel($file->getRealPath())
                        : ['updated' => 0, 'notFound' => 0, 'duplicates' => 0, 'noCatalogNumber' => true];

                    $ar = app()->getLocale() === 'ar';

                    if ($result['noCatalogNumber']) {
                        Notification::make()
                            ->title($ar ? 'تعذر الاستيراد' : 'Import failed')
                            ->body($ar
                                ? 'لم يتم العثور على عمود "الرقم" في الملف، وهو مطلوب لمطابقة السجلات.'
                                : 'No catalog number column was found in the file, which is required to match records.')
                            ->danger()
                            ->send();

                        return;
                    }

                    $body = $ar
                        ? "تم تحديث {$result['updated']} سجل. لم يتم العثور على {$result['notFound']} رقم كتالوج."
                        : "{$result['updated']} records updated. {$result['notFound']} catalog numbers were not found.";

                    if ($result['duplicates'] > 0) {
                        $body .= $ar
                            ? " تم تجاهل {$result['duplicates']} صف مكرر لنفس الرقم (تم اعتماد أول صف فقط)."
                            : " {$result['duplicates']} rows had a repeated catalog number and were skipped (only the first was applied).";
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
     * catalog number. Column set/order follows whatever the export's header
     * row says, so it still works after column mapping or reordering.
     *
     * ponytail: single-threaded row-by-row save, fine at this table's size
     * (thousands, not millions); batch-upsert if that ever changes.
     *
     * @return array{updated: int, notFound: int, duplicates: int, noCatalogNumber: bool}
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
        $catalogNumberColumn = null;
        $updated = 0;
        $notFound = 0;
        $duplicates = 0;
        $seenCatalogNumbers = [];
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

                            if ($field === 'catalog_number') {
                                $catalogNumberColumn = $index;
                            }
                        }
                    }

                    continue;
                }

                if ($catalogNumberColumn === null) {
                    break 2;
                }

                $catalogNumber = trim((string) ($cells[$catalogNumberColumn] ?? ''));

                if ($catalogNumber === '') {
                    continue;
                }

                $catalogNumber = (int) $catalogNumber;

                if (isset($seenCatalogNumbers[$catalogNumber])) {
                    // Same catalog number twice in the file: first occurrence
                    // wins, this one is reported instead of silently
                    // overwriting it (catalog_number is unique in the DB,
                    // so both rows would otherwise target the same record).
                    $duplicates++;

                    continue;
                }

                $seenCatalogNumbers[$catalogNumber] = true;

                $record = BookCatalog::where('catalog_number', $catalogNumber)->first();

                if (! $record) {
                    $notFound++;

                    continue;
                }

                $values = [];

                foreach ($headerMap as $index => $field) {
                    if ($field === 'catalog_number') {
                        continue; // matching key, not editable via import
                    }

                    $value = $cells[$index] ?? null;
                    $value = is_string($value) ? trim($value) : $value;
                    $value = $value === '' ? null : $value;

                    if ($field === 'category' && $value !== null) {
                        $value = $categoryKeysByLabel[$value] ?? $value;
                    }

                    if ($field === 'publisher' && $value !== null) {
                        $value = $publisherKeysByLabel[$value] ?? $value;
                    }

                    $values[$field] = $value;
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
            'noCatalogNumber' => $catalogNumberColumn === null,
        ];
    }
}
