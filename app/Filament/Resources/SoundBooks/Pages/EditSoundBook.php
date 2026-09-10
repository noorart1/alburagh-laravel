<?php

namespace App\Filament\Resources\SoundBooks\Pages;

use App\Filament\Resources\SoundBooks\SoundBookResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSoundBook extends EditRecord
{
    protected static string $resource = SoundBookResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['type'] = 'sound_book';
    
        return $data;
    }
}
