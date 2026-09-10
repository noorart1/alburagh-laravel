<?php

namespace App\Filament\Resources\Books\Pages;

use App\Filament\Resources\Books\BookResource;
use Filament\Resources\Pages\CreateRecord;

class CreateBook extends CreateRecord
{
    protected static string $resource = BookResource::class;
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['type'] = 'book';
    
        return $data;
    }
}
