<?php

namespace App\Filament\Resources\SubscriptionCodes\Pages;

use App\Filament\Resources\SubscriptionCodes\SubscriptionCodeResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSubscriptionCode extends EditRecord
{
    protected static string $resource = SubscriptionCodeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
