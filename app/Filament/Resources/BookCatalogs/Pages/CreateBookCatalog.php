<?php

namespace App\Filament\Resources\BookCatalogs\Pages;

use App\Filament\Resources\BookCatalogs\BookCatalogResource;
use Filament\Resources\Pages\CreateRecord;

class CreateBookCatalog extends CreateRecord
{
    protected static string $resource = BookCatalogResource::class;
}
