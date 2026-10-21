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
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Renderless;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use OpenSpout\Reader\XLSX\Reader as XlsxReader;

class ListBookCatalogs extends ListRecords
{
    protected static string $resource = BookCatalogResource::class;

    protected string $view = 'filament.pages.list-book-catalogs';

    /**
     * The list is a plain JS table (resources/views/filament/book-catalogs/table.blade.php)
     * instead of Filament's table: at 50 rows x 26 columns Livewire's re-render
     * and morph of ~16k nodes made every page/sort/filter change slow. Rows come
     * from fetchBooks() as JSON and cells save through saveCell().
     *
     * @return array<string, array{label: string, type: string, rules: array<int, mixed>, search: bool, options?: array<string, string>}>
     */
    public static function bookColumns(): array
    {
        $ar = app()->getLocale() === 'ar';
        $col = fn (string $label, string $type, array $rules = [], bool $search = false): array => compact('label', 'type', 'rules', 'search');

        return [
            'catalog_number' => $col('الرقم', 'number', [], true),
            'cover_image' => $col($ar ? 'الغلاف' : 'Cover', 'image'),
            'barcode' => $col($ar ? 'باركد الكتاب' : 'Barcode', 'text', ['nullable', 'max:50'], true),
            'series_name' => $col($ar ? 'اسم السلسلة' : 'Series Name', 'text', ['nullable', 'max:255'], true),
            'title' => $col($ar ? 'اسم الكتاب' : 'Book Title', 'text', ['required', 'max:255'], true),
            'category' => $col($ar ? 'الصنف' : 'Category', 'select', ['required', Rule::in(array_keys(BookCatalog::categoryOptions()))]) + ['options' => BookCatalog::categoryOptions()],
            'publisher' => $col($ar ? 'الناشر' : 'Publisher', 'select', ['nullable', Rule::in(array_keys(BookCatalog::publisherOptions()))]) + ['options' => BookCatalog::publisherOptions()],
            'author' => $col($ar ? 'اسم المؤلف' : 'Author', 'text', ['nullable', 'max:255'], true),
            'illustrator' => $col($ar ? 'الرسام' : 'Illustrator', 'text', ['nullable', 'max:255'], true),
            'subject' => $col($ar ? 'موضوع الكتاب' : 'Subject', 'text', ['nullable', 'max:255'], true),
            'edition_number' => $col($ar ? 'رقم الطبعة' : 'Edition', 'number', ['nullable', 'integer', 'min:0']),
            'print_place' => $col($ar ? 'مكان الطباعة' : 'Print Place', 'text', ['nullable', 'max:255'], true),
            'print_year' => $col($ar ? 'سنة الطباعة' : 'Print Year', 'number', ['nullable', 'integer', 'between:1000,9999']),
            'short_description' => $col($ar ? 'نبذة مختصرة' : 'Short Description', 'text', ['nullable', 'string']),
            'pages' => $col($ar ? 'عدد الصفحات' : 'Pages', 'number', ['nullable', 'integer', 'min:0']),
            'price_usd' => $col($ar ? 'دولار' : 'USD', 'number', ['nullable', 'numeric', 'min:0']),
            'price_aed' => $col($ar ? 'درهم' : 'AED', 'number', ['nullable', 'numeric', 'min:0']),
            'price_iqd' => $col($ar ? 'دينار' : 'IQD', 'number', ['nullable', 'numeric', 'min:0']),
            'quantity' => $col($ar ? 'المخزون' : 'Stock', 'number', ['required', 'integer', 'min:0']),
            'cost_iqd' => $col($ar ? 'الكلفة دينار' : 'Cost IQD', 'number', ['nullable', 'numeric', 'min:0']),
            'deposit_number' => $col($ar ? 'رقم الإيداع' : 'Deposit No.', 'text', ['nullable', 'max:100'], true),
            'deposit_year' => $col($ar ? 'سنة الإيداع' : 'Deposit Year', 'number', ['nullable', 'integer', 'between:1000,9999']),
            'size' => $col($ar ? 'القياس' : 'Size', 'text', ['nullable', 'max:100'], true),
            'weight' => $col($ar ? 'الوزن' : 'Weight', 'number', ['nullable', 'numeric', 'min:0']),
            'age_group' => $col($ar ? 'الفئة العمرية' : 'Age Group', 'text', ['nullable', 'max:100'], true),
        ];
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            View::make('filament.book-catalogs.table')->viewData(fn (): array => [
                'columns' => collect(static::bookColumns())->map(fn (array $c): array => collect($c)->except('rules')->all()),
                'prefs' => $prefs = $this->bookPrefs(),
                'initial' => $this->fetchBooks($prefs),
                'editUrl' => BookCatalogResource::getUrl('edit', ['record' => '__ID__']),
            ]),
        ]);
    }

    /**
     * Search, filters, sort, page size and hidden columns, stored on the user
     * so they survive logout. Older values are Filament's column-manager state.
     *
     * @return array<string, mixed>
     */
    protected function bookPrefs(): array
    {
        $stored = auth()->user()?->book_catalogs_table_columns ?? [];

        if (array_is_list($stored)) {
            $stored = ['hidden' => collect($stored)->where('type', 'column')->where('isToggled', false)->pluck('name')->values()->all()];
        }

        return $stored + ['hidden' => [], 'search' => '', 'category' => [], 'publisher' => [], 'sort' => 'catalog_number', 'dir' => 'asc', 'perPage' => 25, 'page' => 1];
    }

    /** @return array{rows: array<int, array<string, mixed>>, total: int, lastPage: int, page: int} */
    #[Renderless]
    public function fetchBooks(array $q): array
    {
        $columns = static::bookColumns();
        $q = [
            'hidden' => array_values(array_intersect((array) ($q['hidden'] ?? []), array_keys($columns))),
            'search' => trim((string) ($q['search'] ?? '')),
            'category' => array_values(array_intersect((array) ($q['category'] ?? []), array_keys(BookCatalog::categoryOptions()))),
            'publisher' => array_values(array_intersect((array) ($q['publisher'] ?? []), array_keys(BookCatalog::publisherOptions()))),
            'sort' => isset($columns[$q['sort'] ?? '']) && $q['sort'] !== 'cover_image' ? $q['sort'] : 'catalog_number',
            'dir' => ($q['dir'] ?? '') === 'desc' ? 'desc' : 'asc',
            'perPage' => in_array((int) ($q['perPage'] ?? 0), [10, 25, 50, 100], true) ? (int) $q['perPage'] : 25,
            'page' => max(1, (int) ($q['page'] ?? 1)),
        ];

        // user is null in the re-render right after a sync-queue export (its job calls auth()->forgetGuards())
        if (($user = auth()->user()) && $user->book_catalogs_table_columns !== $q) {
            $user->update(['book_catalogs_table_columns' => $q]);
        }

        $books = static::filterBooks(BookCatalog::query(), $q)
            ->orderBy($q['sort'], $q['dir'])
            ->orderBy('id')
            ->paginate($q['perPage'], ['id', ...array_keys($columns)], 'page', $q['page']);

        if ($books->isEmpty() && $q['page'] > 1) {
            return $this->fetchBooks([...$q, 'page' => $books->lastPage()]);
        }

        $disk = Storage::disk('public');

        return [
            'rows' => $books->getCollection()->map(fn (BookCatalog $book): array => [
                ...$book->toArray(),
                'cover_image' => $book->cover_image ? $disk->url($book->cover_image) : null,
            ])->all(),
            'total' => $books->total(),
            'lastPage' => $books->lastPage(),
            'page' => $books->currentPage(),
        ];
    }

    /** Search + category/publisher filters of the list; the Excel export uses them too. */
    protected static function filterBooks(Builder $query, array $q): Builder
    {
        $search = trim((string) ($q['search'] ?? ''));
        $searchable = array_keys(array_filter(static::bookColumns(), fn (array $c): bool => $c['search']));

        return $query
            ->when($search !== '', fn (Builder $query) => $query->where(function (Builder $query) use ($searchable, $search): void {
                foreach ($searchable as $name) {
                    $query->orWhere($name, 'like', '%'.$search.'%');
                }
            }))
            ->when($q['category'] ?? [], fn (Builder $query, array $v) => $query->whereIn('category', $v))
            ->when($q['publisher'] ?? [], fn (Builder $query, array $v) => $query->whereIn('publisher', $v));
    }

    /** @return array{value?: mixed, error?: string} */
    #[Renderless]
    public function saveCell(int $id, string $field, mixed $value): array
    {
        $column = static::bookColumns()[$field] ?? null;
        abort_if(! $column || $column['type'] === 'image', 422);

        $rules = $field === 'catalog_number'
            ? ['required', 'integer', Rule::unique('book_catalogs', 'catalog_number')->ignore($id)]
            : $column['rules'];
        $value = $value === '' ? null : $value;

        $validator = Validator::make(['value' => $value], ['value' => $rules], [], ['value' => $column['label']]);

        if ($validator->fails()) {
            return ['error' => $validator->errors()->first()];
        }

        $book = BookCatalog::findOrFail($id);
        $book->update([$field => $value]);

        return ['value' => $book->refresh()->getAttribute($field)];
    }

    #[Renderless]
    public function deleteBooks(array $ids): void
    {
        BookCatalog::whereKey($ids)->get()->each->delete();
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
                ->columnMappingColumns(3)
                ->modifyQueryUsing(function (Builder $query, array $options): Builder {
                    $query = static::filterBooks($query, $this->bookPrefs());

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
