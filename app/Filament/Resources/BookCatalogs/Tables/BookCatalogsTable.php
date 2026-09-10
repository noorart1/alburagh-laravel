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
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class BookCatalogsTable
{

    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('catalog_number')
                    ->width('74px')
                    ->label('#')
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
                    ->formatStateUsing(fn (?string $state): ?string => match ($state) {
                        'book' => app()->getLocale() === 'ar' ? 'كتاب' : 'Book',
                        'game' => app()->getLocale() === 'ar' ? 'لعبة' : 'Game',
                        'islamic' => app()->getLocale() === 'ar' ? 'إسلامي' : 'Islamic',
                        default => $state,
                    })
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('publisher')
                    ->width('60px')
                    ->limit(17)
                    ->label(app()->getLocale() === 'ar' ? 'الناشر' : 'Publisher')
                    ->formatStateUsing(fn (?string $state): ?string => match ($state) {
                        'dar_alburagh' => app()->getLocale() === 'ar'
                            ? 'دار البراق لثقافة الأطفال'
                            : 'Dar Al-Buraq for Children’s Culture',
                        'dar_maheroon' => app()->getLocale() === 'ar'
                            ? 'دار ماهرون للنشر والتوزيع'
                            : 'Dar Maheroon for Publishing and Distribution',
                        'supplies' => app()->getLocale() === 'ar' ? 'توريدات' : 'Supplies',
                        default => $state,
                    })
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
            ->persistFiltersInSession()
            ->persistSearchInSession()
            ->persistColumnSearchesInSession();
    }
}
