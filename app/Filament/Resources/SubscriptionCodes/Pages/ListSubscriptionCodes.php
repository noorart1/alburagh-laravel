<?php

namespace App\Filament\Resources\SubscriptionCodes\Pages;

use App\Filament\Resources\SubscriptionCodes\SubscriptionCodeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSubscriptionCodes extends ListRecords
{
    protected static string $resource = SubscriptionCodeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
