<?php

namespace App\Filament\Resources\BookCatalogs\Pages;

use App\Filament\Resources\BookCatalogs\BookCatalogResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewBookCatalog extends ViewRecord
{
    protected static string $resource = BookCatalogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->label(app()->getLocale() === 'ar' ? 'تعديل' : 'Edit'),
        ];
    }
}
