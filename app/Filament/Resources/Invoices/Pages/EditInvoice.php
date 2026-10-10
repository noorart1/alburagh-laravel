<?php

namespace App\Filament\Resources\Invoices\Pages;

use App\Filament\Resources\Invoices\InvoiceResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditInvoice extends EditRecord
{
    protected static string $resource = InvoiceResource::class;

    protected function afterSave(): void
    {
        $this->record->recalculateTotal();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('excel')
                ->label(app()->getLocale() === 'ar' ? 'تصدير Excel' : 'Export Excel')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->action(fn () => InvoiceResource::downloadExcel($this->record)),

            DeleteAction::make()
                ->label(app()->getLocale() === 'ar' ? 'حذف' : 'Delete'),
        ];
    }
}
