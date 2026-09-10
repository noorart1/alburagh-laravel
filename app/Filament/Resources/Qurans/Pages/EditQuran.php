<?php

namespace App\Filament\Resources\Qurans\Pages;

use App\Filament\Resources\Qurans\QuranResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditQuran extends EditRecord
{
    protected static string $resource = QuranResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['type'] = 'quran';
    
        return $data;
    }
}
