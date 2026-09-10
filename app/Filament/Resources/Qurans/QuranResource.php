<?php

namespace App\Filament\Resources\Qurans;

use App\Filament\Resources\Qurans\Pages\CreateQuran;
use App\Filament\Resources\Qurans\Pages\EditQuran;
use App\Filament\Resources\Qurans\Pages\ListQurans;
use App\Filament\Resources\Qurans\Schemas\QuranForm;
use App\Filament\Resources\Qurans\Tables\QuransTable;
use App\Models\Content;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class QuranResource extends Resource
{
    protected static ?string $model = Content::class;

    protected static ?string $recordTitleAttribute = 'title';

    protected static string|BackedEnum|null $navigationIcon =
        Heroicon::OutlinedRectangleStack;

    protected static ?int $navigationSort = 3;

    public static function getNavigationLabel(): string
    {
        return __('admin.quran');
    }

    public static function getModelLabel(): string
    {
        return __('admin.quran');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.quran');
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
            ->where('type', 'quran');
    }

    public static function form(Schema $schema): Schema
    {
        return QuranForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return QuransTable::configure($table);
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
            'index' => ListQurans::route('/'),
            'create' => CreateQuran::route('/create'),
            'edit' => EditQuran::route('/{record}/edit'),
        ];
    }
}