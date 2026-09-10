<?php

namespace App\Filament\Resources\BookCatalogs;

use App\Filament\Resources\BookCatalogs\Pages\CreateBookCatalog;
use App\Filament\Resources\BookCatalogs\Pages\EditBookCatalog;
use App\Filament\Resources\BookCatalogs\Pages\ListBookCatalogs;
use App\Filament\Resources\BookCatalogs\Pages\ViewBookCatalog;
use App\Filament\Resources\BookCatalogs\Schemas\BookCatalogForm;
use App\Filament\Resources\BookCatalogs\Schemas\BookCatalogInfolist;
use App\Filament\Resources\BookCatalogs\Tables\BookCatalogsTable;
use App\Models\BookCatalog;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class BookCatalogResource extends Resource
{
    protected static ?string $model = BookCatalog::class;

    protected static ?int $navigationSort = 20;

    public static function getNavigationLabel(): string
    {
        return app()->getLocale() === 'ar' ? 'فهرس الكتب' : 'Book Catalog';
    }

    public static function getModelLabel(): string
    {
        return app()->getLocale() === 'ar' ? 'كتاب' : 'Book';
    }

    public static function getPluralModelLabel(): string
    {
        return app()->getLocale() === 'ar' ? 'الكتب' : 'Books';
    }

    public static function getNavigationGroup(): ?string
    {
        return app()->getLocale() === 'ar' ? 'إدارة الكتب' : 'Book Management';
    }

    public static function form(Schema $schema): Schema
    {
        return BookCatalogForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return BookCatalogInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BookCatalogsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBookCatalogs::route('/'),
            'create' => CreateBookCatalog::route('/create'),
            'view' => ViewBookCatalog::route('/{record}'),
            'edit' => EditBookCatalog::route('/{record}/edit'),
        ];
    }
}
