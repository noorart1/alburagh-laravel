<?php

namespace App\Filament\Resources\SoundBooks\Pages;

use App\Filament\Resources\SoundBooks\SoundBookResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSoundBook extends CreateRecord
{
    protected static string $resource = SoundBookResource::class;
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['type'] = 'sound_book';
    
        return $data;
    }
    
}
