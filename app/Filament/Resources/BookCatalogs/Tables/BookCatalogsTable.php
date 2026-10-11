<?php

namespace App\Filament\Resources\BookCatalogs\Tables;

use App\Models\BookCatalog;
use App\Filament\Exports\BookCatalogExporter;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ExportBulkAction;
use Filament\Actions\Exports\Enums\ExportFormat;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\SelectColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\TextInputColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;

class BookCatalogsTable
{
    /**
     * One entry per Category/Publisher option, shared between the ✓ columns
     * below and the query filter in modifyQueryUsing() so both stay in sync.
     *
     * @return Collection<int, array{name: string, field: string, value: string, label: string}>
     */
    protected static function optionColumns(): Collection
    {
        return collect(['category' => BookCatalog::categoryOptions(), 'publisher' => BookCatalog::publisherOptions()])
            ->flatMap(fn (array $options, string $field) => collect($options)->map(
                fn (string $label, string $key) => [
                    'name' => "{$field}_{$key}",
                    'field' => $field,
                    'value' => $key,
                    'label' => $label,
                ]
            ))
            ->values();
    }

    public static function configure(Table $table): Table
    {
        $optionColumns = static::optionColumns();
        $ar = app()->getLocale() === 'ar';

        // Every data column is edited in place (no need to open the edit page).
        $input = fn (string $name, string $width, string $label, array $rules): TextInputColumn => TextInputColumn::make($name)
            ->width($width)
            ->label($label)
            ->rules($rules)
            // step: decimals like 4.50 in number inputs; title: full value on hover when clipped
            ->extraInputAttributes(['step' => 'any', 'x-bind:title' => 'state'])
            ->toggleable();

        return $table
            ->columns([
                // Temporary display number (e.g. 1010+), when set, shows beside the real one.
                $input('catalog_number', '84px', 'الرقم', [])
                    ->rules(fn (BookCatalog $record): array => [
                        'required',
                        'integer',
                        Rule::unique('book_catalogs', 'catalog_number')->ignore($record->getKey()),
                    ])
                    ->type('number')
                    ->prefix(fn (BookCatalog $record): ?string => $record->temporary_catalog_number ?: null)
                    ->toggleable(false)
                    ->searchable(),

                ImageColumn::make('cover_image')
                    ->width('68px')
                    ->label(app()->getLocale() === 'ar' ? 'الغلاف' : 'Cover')
                    ->disk('public')
                    ->square()
                    ->height(52)
                    ->extraImgAttributes(['loading' => 'lazy'])
                    ->toggleable(),

                $input('barcode', '160px', $ar ? 'باركد الكتاب' : 'Barcode', ['nullable', 'max:50'])
                    ->searchable(),

                $input('series_name', '150px', $ar ? 'اسم السلسلة' : 'Series Name', ['nullable', 'max:255'])
                    ->searchable(),

                $input('title', '220px', $ar ? 'اسم الكتاب' : 'Book Title', ['required', 'max:255'])
                    ->searchable(),

                SelectColumn::make('category')
                    ->width('120px')
                    ->label($ar ? 'الصنف' : 'Category')
                    ->options(BookCatalog::categoryOptions())
                    ->rules(['required', Rule::in(array_keys(BookCatalog::categoryOptions()))])
                    ->selectablePlaceholder(false)
                    ->searchable()
                    ->toggleable(),

                SelectColumn::make('publisher')
                    ->width('170px')
                    ->label($ar ? 'الناشر' : 'Publisher')
                    ->options(BookCatalog::publisherOptions())
                    ->rules(['nullable', Rule::in(array_keys(BookCatalog::publisherOptions()))])
                    ->searchable()
                    ->toggleable(),

                $input('author', '130px', $ar ? 'اسم المؤلف' : 'Author', ['nullable', 'max:255'])
                    ->searchable(),

                $input('illustrator', '140px', $ar ? 'الرسام' : 'Illustrator', ['nullable', 'max:255'])
                    ->searchable(),

                $input('subject', '140px', $ar ? 'موضوع الكتاب' : 'Subject', ['nullable', 'max:255'])
                    ->searchable(),

                $input('edition_number', '82px', $ar ? 'رقم الطبعة' : 'Edition', ['nullable', 'integer', 'min:0'])
                    ->type('number'),

                $input('print_place', '110px', $ar ? 'مكان الطباعة' : 'Print Place', ['nullable', 'max:255'])
                    ->searchable(),

                $input('print_year', '94px', $ar ? 'سنة الطباعة' : 'Print Year', ['nullable', 'integer', 'between:1000,9999'])
                    ->type('number'),

                $input('short_description', '260px', $ar ? 'نبذة مختصرة' : 'Short Description', ['nullable', 'string']),

                $input('pages', '88px', $ar ? 'عدد الصفحات' : 'Pages', ['nullable', 'integer', 'min:0'])
                    ->type('number'),

                $input('price_usd', '84px', $ar ? 'دولار' : 'USD', ['nullable', 'numeric', 'min:0'])
                    ->type('number'),

                $input('price_aed', '84px', $ar ? 'درهم' : 'AED', ['nullable', 'numeric', 'min:0'])
                    ->type('number'),

                $input('price_iqd', '94px', $ar ? 'دينار' : 'IQD', ['nullable', 'numeric', 'min:0'])
                    ->type('number'),

                $input('quantity', '100px', $ar ? 'الكمية' : 'Quantity', ['required', 'integer', 'min:0'])
                    ->type('number')
                    ->sortable(),

                $input('cost_iqd', '94px', $ar ? 'الكلفة دينار' : 'Cost IQD', ['nullable', 'numeric', 'min:0'])
                    ->type('number'),

                $input('deposit_number', '100px', $ar ? 'رقم الإيداع' : 'Deposit No.', ['nullable', 'max:100'])
                    ->searchable(),

                $input('deposit_year', '96px', $ar ? 'سنة الإيداع' : 'Deposit Year', ['nullable', 'integer', 'between:1000,9999'])
                    ->type('number'),

                $input('size', '140px', $ar ? 'القياس' : 'Size', ['nullable', 'max:100'])
                    ->searchable(),

                $input('weight', '100px', $ar ? 'الوزن' : 'Weight', ['nullable', 'numeric', 'min:0'])
                    ->type('number'),

                $input('age_group', '105px', $ar ? 'الفئة العمرية' : 'Age Group', ['nullable', 'max:100'])
                    ->searchable(),

                // One ✓ column per Category/Publisher option (hidden by default; ticking it
                // in the column manager both shows it and filters to matching records, see
                // the modifyQueryUsing() below).
                ...$optionColumns->map(
                    fn (array $option) => TextColumn::make($option['name'])
                        ->label($option['label'])
                        ->state(fn (BookCatalog $record): string => $record->{$option['field']} === $option['value'] ? '✓' : '')
                        ->alignCenter()
                        ->toggleable(isToggledHiddenByDefault: true)
                )->all(),
            ])
            ->filters([
                SelectFilter::make('category')
                    ->label(app()->getLocale() === 'ar' ? 'الصنف' : 'Category')
                    ->options(BookCatalog::categoryOptions())
                    ->multiple(),
                SelectFilter::make('publisher')
                    ->label(app()->getLocale() === 'ar' ? 'الناشر' : 'Publisher')
                    ->options(BookCatalog::publisherOptions())
                    ->multiple(),
            ])
            ->recordUrl(null)
            ->recordActions([
                ViewAction::make()
                    ->label(app()->getLocale() === 'ar' ? 'عرض' : 'View'),
                EditAction::make()
                    ->label(app()->getLocale() === 'ar' ? 'تعديل' : 'Edit'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    ExportBulkAction::make('exportSelected')
                        ->label(app()->getLocale() === 'ar' ? 'تصدير المحدد إلى Excel' : 'Export selected to Excel')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->color('success')
                        ->exporter(BookCatalogExporter::class)
                        ->formats([
                            ExportFormat::Xlsx,
                        ])
                        ->enableVisibleTableColumnsByDefault()
                        ->columnMappingColumns(3)
                        ->maxRows(10000),

                    DeleteBulkAction::make(),
                ]),
            ])
            ->modifyQueryUsing(function (Builder $query): Builder {
                return $query
                    ->orderByRaw("
                        CASE
                            WHEN temporary_catalog_number IS NULL
                              OR temporary_catalog_number = ''
                            THEN catalog_number
                            ELSE CAST(temporary_catalog_number AS UNSIGNED)
                        END ASC
                    ")
                    ->orderByRaw("
                        CASE
                            WHEN temporary_catalog_number IS NULL
                              OR temporary_catalog_number = ''
                            THEN 0
                            ELSE 1
                        END ASC
                    ")
                    ->orderBy('catalog_number', 'asc');
            })
            ->modifyQueryUsing(function (Builder $query, $livewire) use ($optionColumns): Builder {
                // Column manager doubles as the filter UI: whichever ✓ columns
                // are toggled on narrow the results to matching records (OR
                // across every ticked option, whether category or publisher).
                $toggled = collect($livewire->tableColumns ?? [])
                    ->where('type', 'column')
                    ->pluck('isToggled', 'name');

                $activeOptions = $optionColumns->filter(
                    fn (array $option) => (bool) ($toggled[$option['name']] ?? false)
                );

                if ($activeOptions->isEmpty()) {
                    return $query;
                }

                return $query->where(function (Builder $query) use ($activeOptions): void {
                    foreach ($activeOptions->groupBy('field') as $field => $options) {
                        $query->orWhereIn($field, $options->pluck('value'));
                    }
                });
            })
            ->columnManagerColumns(3)
            ->persistFiltersInSession()
            ->persistSearchInSession()
            ->persistColumnSearchesInSession();
    }
}
