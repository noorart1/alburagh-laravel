<?php

namespace App\Filament\Resources\Qurans\Pages;

use App\Filament\Resources\Qurans\QuranResource;
use Filament\Resources\Pages\CreateRecord;

class CreateQuran extends CreateRecord
{
    protected static string $resource = QuranResource::class;
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['type'] = 'quran';
    
        return $data;
    }
    
}
