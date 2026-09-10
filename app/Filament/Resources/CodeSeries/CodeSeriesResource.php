<?php

namespace App\Filament\Resources\CodeSeries;

use App\Filament\Resources\CodeSeries\Pages\CreateCodeSeries;
use App\Filament\Resources\CodeSeries\Pages\EditCodeSeries;
use App\Filament\Resources\CodeSeries\Pages\ListCodeSeries;
use App\Filament\Resources\CodeSeries\Schemas\CodeSeriesForm;
use App\Filament\Resources\CodeSeries\Tables\CodeSeriesTable;
use App\Models\CodeSeries;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class CodeSeriesResource extends Resource
{
    protected static string|\UnitEnum|null $navigationGroup = 'Subscriptions';
    
    protected static ?int $navigationSort = 2;
    protected static ?string $model = CodeSeries::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'prefix';

    public static function form(Schema $schema): Schema
    {
        return CodeSeriesForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CodeSeriesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }
    public static function getNavigationGroup(): ?string
    {
        return app()->getLocale() === 'ar'
            ? 'إدارة التطبيق'
            : 'App Management';
    }
    public static function getPages(): array
    {
        return [
            'index' => ListCodeSeries::route('/'),
            'create' => CreateCodeSeries::route('/create'),
            'edit' => EditCodeSeries::route('/{record}/edit'),
        ];
    }
}
