<?php

namespace App\Filament\Resources\SoundBooks\Pages;

use App\Filament\Resources\SoundBooks\SoundBookResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSoundBooks extends ListRecords
{
    protected static string $resource = SoundBookResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
