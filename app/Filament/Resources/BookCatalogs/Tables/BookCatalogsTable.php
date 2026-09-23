<?php

namespace App\Filament\Resources\BookCatalogs\Tables;

use App\Models\BookCatalog;
use App\Filament\Exports\BookCatalogExporter;
use App\Filament\Resources\BookCatalogs\BookCatalogResource;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ExportBulkAction;
use Filament\Actions\Exports\Enums\ExportFormat;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

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

        return $table
            ->columns([
                TextColumn::make('catalog_number')
                    ->width('74px')
                    ->label('الرقم')
                    ->formatStateUsing(
                        fn ($state, BookCatalog $record): string =>
                            filled($record->temporary_catalog_number)
                                ? (string) $record->temporary_catalog_number
                                : (string) $state
                    )
                    ->searchable()
                    ->copyable(),

                ImageColumn::make('cover_image')
                    ->width('68px')
                    ->label(app()->getLocale() === 'ar' ? 'الغلاف' : 'Cover')
                    ->disk('public')
                    ->square()
                    ->height(52)
                    ->extraImgAttributes(['loading' => 'lazy'])
                    ->toggleable(),

                TextColumn::make('barcode')
                    ->width('138px')
                    ->label(app()->getLocale() === 'ar' ? 'باركد الكتاب' : 'Barcode')
                    ->searchable()
                    ->copyable()
                    ->toggleable(),

                TextColumn::make('series_name')
                    ->width('55px')
                    ->label(app()->getLocale() === 'ar' ? 'اسم السلسلة' : 'Series Name')
                    ->searchable()
                    ->limit(14)
                    ->toggleable(),

                TextColumn::make('title')
                    ->width('68px')
                    ->label(app()->getLocale() === 'ar' ? 'اسم الكتاب' : 'Book Title')
                    ->searchable()
                    ->limit(17)
                    ->toggleable(),
                TextColumn::make('category')
                    ->width('90px')
                    ->limit(17)
                    ->label(app()->getLocale() === 'ar' ? 'الصنف' : 'Category')
                    ->formatStateUsing(fn (?string $state): ?string => BookCatalog::categoryOptions()[$state] ?? $state)
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('publisher')
                    ->width('60px')
                    ->limit(17)
                    ->label(app()->getLocale() === 'ar' ? 'الناشر' : 'Publisher')
                    ->formatStateUsing(fn (?string $state): ?string => BookCatalog::publisherOptions()[$state] ?? $state)
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('author')
                    ->width('105px')
                    ->label(app()->getLocale() === 'ar' ? 'اسم المؤلف' : 'Author')
                    ->searchable()
                    ->limit(28)
                    ->toggleable(),

                TextColumn::make('illustrator')
                    ->width('80px')
                    ->label(app()->getLocale() === 'ar' ? 'الرسام' : 'Illustrator')
                    ->searchable()
                    ->limit(15)
                    ->toggleable(),

                TextColumn::make('subject')
                    ->width('60px')
                    ->label(app()->getLocale() === 'ar' ? 'موضوع الكتاب' : 'Subject')
                    ->searchable()
                    ->limit(15)
                    ->toggleable(),

                TextColumn::make('edition_number')
                    ->width('82px')
                    ->label(app()->getLocale() === 'ar' ? 'رقم الطبعة' : 'Edition')
                    ->toggleable(),

                TextColumn::make('print_place')
                    ->width('95px')
                    ->label(app()->getLocale() === 'ar' ? 'مكان الطباعة' : 'Print Place')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('print_year')
                    ->width('94px')
                    ->label(app()->getLocale() === 'ar' ? 'سنة الطباعة' : 'Print Year')
                    ->toggleable(),

                TextColumn::make('short_description')
                    ->width('110px')
                    ->label(app()->getLocale() === 'ar' ? 'نبذة مختصرة' : 'Short Description')
                    ->limit(20)
                    ->toggleable(),

                TextColumn::make('pages')
                    ->width('88px')
                    ->label(app()->getLocale() === 'ar' ? 'عدد الصفحات' : 'Pages')
                    ->toggleable(),

                TextColumn::make('price_usd')
                    ->width('74px')
                    ->label(app()->getLocale() === 'ar' ? 'دولار' : 'USD')
                    ->numeric(decimalPlaces: 2)
                    ->toggleable(),

                TextColumn::make('price_aed')
                    ->width('74px')
                    ->label(app()->getLocale() === 'ar' ? 'درهم' : 'AED')
                    ->numeric(decimalPlaces: 2)
                    ->toggleable(),

                TextColumn::make('price_iqd')
                    ->width('84px')
                    ->label(app()->getLocale() === 'ar' ? 'دينار' : 'IQD')
                    ->numeric(decimalPlaces: 0)
                    ->toggleable(),

                TextColumn::make('deposit_number')
                    ->width('88px')
                    ->label(app()->getLocale() === 'ar' ? 'رقم الإيداع' : 'Deposit No.')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('deposit_year')
                    ->width('96px')
                    ->label(app()->getLocale() === 'ar' ? 'سنة الإيداع' : 'Deposit Year')
                    ->toggleable(),

                TextColumn::make('size')
                    ->width('118px')
                    ->label(app()->getLocale() === 'ar' ? 'القياس' : 'Size')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('weight')
                    ->width('86px')
                    ->label(app()->getLocale() === 'ar' ? 'الوزن' : 'Weight')
                    ->numeric(decimalPlaces: 3)
                    ->toggleable(),

                TextColumn::make('age_group')
                    ->width('105px')
                    ->label(app()->getLocale() === 'ar' ? 'الفئة العمرية' : 'Age Group')
                    ->searchable()
                    ->toggleable(),

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
            ->recordUrl(fn (BookCatalog $record): string => BookCatalogResource::getUrl('edit', ['record' => $record]))
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
            ->deferLoading()
            ->persistFiltersInSession()
            ->persistSearchInSession()
            ->persistColumnSearchesInSession();
    }
}
