<?php

namespace App\Filament\Resources\BookCatalogs\Pages;

use App\Filament\Resources\BookCatalogs\BookCatalogResource;
use App\Models\BookCatalog;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateBookCatalog extends CreateRecord
{
    protected static string $resource = BookCatalogResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function handleRecordCreation(array $data): Model
    {
        $record = new BookCatalog();

        $record->fill($data);

        $record->catalog_number = filled($data['catalog_number'] ?? null)
            ? (int) $data['catalog_number']
            : max(
                1000,
                ((int) BookCatalog::query()->max('catalog_number')) + 1
            );

        if (array_key_exists('temporary_catalog_number', $data)) {
            $record->temporary_catalog_number = $data['temporary_catalog_number'];
        }

        $record->save();

        return $record;
    }
}
