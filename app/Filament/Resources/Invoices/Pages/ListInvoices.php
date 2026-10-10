<?php

namespace App\Filament\Resources\Invoices\Pages;

use App\Filament\Resources\Invoices\InvoiceResource;
use App\Models\Invoice;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Utilities\Get;

class ListInvoices extends ListRecords
{
    protected static string $resource = InvoiceResource::class;

    protected function getHeaderActions(): array
    {
        $ar = app()->getLocale() === 'ar';

        return [
            CreateAction::make()
                ->label($ar ? 'إنشاء فاتورة' : 'New Invoice'),

            // All invoices of one buyer → one combined Excel (optionally merged for good).
            Action::make('buyerCombined')
                ->label($ar ? 'فاتورة مجمّعة لمشتري' : 'Combined invoice for buyer')
                ->icon('heroicon-o-document-duplicate')
                ->color('success')
                ->modalSubmitActionLabel($ar ? 'تصدير Excel' : 'Export Excel')
                ->schema([
                    Select::make('buyer_name')
                        ->label($ar ? 'المشتري' : 'Buyer')
                        ->options(fn (): array => Invoice::query()->distinct()->orderBy('buyer_name')->pluck('buyer_name', 'buyer_name')->all())
                        ->searchable()
                        ->required()
                        ->live(),

                    Select::make('currency')
                        ->label($ar ? 'العملة' : 'Currency')
                        ->options(fn (Get $get): array => collect(Invoice::currencyOptions())
                            ->only(Invoice::where('buyer_name', $get('buyer_name'))->distinct()->pluck('currency')->all())
                            ->all())
                        ->default('iqd')
                        ->required(),

                    Checkbox::make('merge')
                        ->label($ar ? 'دمج الفواتير نهائياً في فاتورة واحدة (تُحذف الفواتير الأخرى)' : 'Also merge permanently into one invoice (others are deleted)'),
                ])
                ->action(function (array $data) use ($ar) {
                    $invoices = Invoice::where('buyer_name', $data['buyer_name'])
                        ->where('currency', $data['currency'])
                        ->get();

                    if ($invoices->isEmpty()) {
                        Notification::make()
                            ->title($ar ? 'لا توجد فواتير لهذا المشتري بهذه العملة' : 'No invoices for this buyer in this currency')
                            ->danger()
                            ->send();

                        return null;
                    }

                    return InvoiceResource::downloadExcel(
                        $data['merge'] && $invoices->count() > 1 ? Invoice::merge($invoices) : $invoices
                    );
                }),
        ];
    }
}
