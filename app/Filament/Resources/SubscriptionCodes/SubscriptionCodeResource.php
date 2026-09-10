<?php

namespace App\Filament\Resources\SubscriptionCodes;

use App\Filament\Resources\SubscriptionCodes\Pages\CreateSubscriptionCode;
use App\Filament\Resources\SubscriptionCodes\Pages\EditSubscriptionCode;
use App\Filament\Resources\SubscriptionCodes\Pages\ListSubscriptionCodes;
use App\Filament\Resources\SubscriptionCodes\Schemas\SubscriptionCodeForm;
use App\Filament\Resources\SubscriptionCodes\Tables\SubscriptionCodesTable;
use App\Models\SubscriptionCode;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class SubscriptionCodeResource extends Resource
{
    protected static ?int $navigationSort = 1;

    protected static ?string $model = SubscriptionCode::class;

    protected static string|BackedEnum|null $navigationIcon =
        Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'code';

    public static function getNavigationLabel(): string
    {
        return __('admin.subscription_codes');
    }

    public static function getModelLabel(): string
    {
        return __('admin.subscription_code');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.subscription_codes');
    }

    public static function getNavigationGroup(): ?string
    {
        return app()->getLocale() === 'ar'
            ? 'إدارة التطبيق'
            : 'App Management';
    }

    public static function form(Schema $schema): Schema
    {
        return SubscriptionCodeForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SubscriptionCodesTable::configure($table);
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
            'index' => ListSubscriptionCodes::route('/'),
            'create' => CreateSubscriptionCode::route('/create'),
            'edit' => EditSubscriptionCode::route('/{record}/edit'),
        ];
    }
}