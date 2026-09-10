<?php

namespace App\Filament\Resources\SoundBooks;

use App\Filament\Resources\SoundBooks\Pages\CreateSoundBook;
use App\Filament\Resources\SoundBooks\Pages\EditSoundBook;
use App\Filament\Resources\SoundBooks\Pages\ListSoundBooks;
use App\Filament\Resources\SoundBooks\Schemas\SoundBookForm;
use App\Filament\Resources\SoundBooks\Tables\SoundBooksTable;
use App\Models\Content;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SoundBookResource extends Resource
{
    protected static ?string $model = Content::class;

    protected static ?string $recordTitleAttribute = 'title';

    protected static string|BackedEnum|null $navigationIcon =
        Heroicon::OutlinedRectangleStack;

    protected static ?int $navigationSort = 4;

    public static function getNavigationLabel(): string
    {
        return __('admin.sound_books');
    }

    public static function getModelLabel(): string
    {
        return __('admin.sound_book');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.sound_books');
    }

    public static function getNavigationGroup(): ?string
    {
        return app()->getLocale() === 'ar'
            ? 'إدارة التطبيق'
            : 'App Management';
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('type', 'sound_book');
    }

    public static function form(Schema $schema): Schema
    {
        return SoundBookForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SoundBooksTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSoundBooks::route('/'),
            'create' => CreateSoundBook::route('/create'),
            'edit' => EditSoundBook::route('/{record}/edit'),
        ];
    }
}