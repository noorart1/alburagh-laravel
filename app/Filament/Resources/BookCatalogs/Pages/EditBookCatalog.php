<?php

namespace App\Filament\Resources\BookCatalogs\Pages;

use App\Filament\Resources\BookCatalogs\BookCatalogResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditBookCatalog extends EditRecord
{
    protected static string $resource = BookCatalogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            $this->getSaveFormAction()
                ->label(app()->getLocale() === 'ar' ? 'حفظ' : 'Save')
                ->formId('form'),

            Action::make('close')
                ->label(app()->getLocale() === 'ar' ? 'إغلاق' : 'Close')
                ->color('gray')
                ->url(BookCatalogResource::getUrl('index')),

            DeleteAction::make()
                ->label(app()->getLocale() === 'ar' ? 'حذف' : 'Delete'),
        ];
    }

    protected function getFormActions(): array
    {
        return [];
    }
}
