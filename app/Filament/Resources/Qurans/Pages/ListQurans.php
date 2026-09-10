<?php

namespace App\Filament\Resources\Qurans\Pages;

use App\Filament\Resources\Qurans\QuranResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListQurans extends ListRecords
{
    protected static string $resource = QuranResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
