<?php

namespace App\Filament\Resources\Invoices\Tables;

use App\Filament\Resources\Invoices\InvoiceResource;
use App\Models\Invoice;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Illuminate\Support\Collection;

class InvoicesTable
{
    public static function configure(Table $table): Table
    {
        $ar = app()->getLocale() === 'ar';

        return $table
            ->columns([
                TextColumn::make('id')
                    ->label($ar ? 'رقم الفاتورة' : 'Invoice #')
                    ->sortable(),

                TextColumn::make('buyer_name')
                    ->label($ar ? 'اسم المشتري' : 'Buyer')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('date')
                    ->label($ar ? 'التاريخ' : 'Date')
                    ->date('Y-m-d')
                    ->sortable(),

                TextColumn::make('currency')
                    ->label($ar ? 'العملة' : 'Currency')
                    ->formatStateUsing(fn (string $state): string => Invoice::currencyOptions()[$state] ?? $state)
                    ->badge(),

                TextColumn::make('items_count')
                    ->label($ar ? 'عدد الكتب' : 'Lines')
                    ->counts('items'),

                // ponytail: per-buyer sum adds raw numbers across currencies; filter by
                // currency for a meaningful total, or group by buyer+currency if mixing is common.
                TextColumn::make('total')
                    ->label($ar ? 'الإجمالي' : 'Total')
                    ->formatStateUsing(fn ($state): string => Invoice::formatAmount($state))
                    ->sortable()
                    ->summarize(Sum::make()
                        ->label($ar ? 'مجموع المشتريات' : 'Total purchases')
                        ->formatStateUsing(fn ($state): string => Invoice::formatAmount($state))),
            ])
            ->groups([
                Group::make('buyer_name')
                    ->label($ar ? 'المشتري' : 'Buyer')
                    ->collapsible(),
            ])
            ->filters([
                SelectFilter::make('currency')
                    ->label($ar ? 'العملة' : 'Currency')
                    ->options(Invoice::currencyOptions()),
                SelectFilter::make('buyer_name')
                    ->label($ar ? 'المشتري' : 'Buyer')
                    ->options(fn (): array => Invoice::query()->distinct()->orderBy('buyer_name')->pluck('buyer_name', 'buyer_name')->all())
                    ->searchable(),
            ])
            ->defaultSort('date', 'desc')
            ->recordActions([
                Action::make('excel')
                    ->label('Excel')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->action(fn (Invoice $record) => InvoiceResource::downloadExcel($record)),
                EditAction::make()
                    ->label($ar ? 'تعديل' : 'Edit'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('exportCombined')
                        ->label($ar ? 'تصدير مجمّع إلى Excel' : 'Export combined to Excel')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->color('success')
                        ->action(function (Collection $records) use ($ar) {
                            if (! Invoice::sameBuyerAndCurrency($records)) {
                                static::notifySameBuyerRequired($ar);

                                return null;
                            }

                            return InvoiceResource::downloadExcel($records);
                        })
                        ->deselectRecordsAfterCompletion(),

                    BulkAction::make('merge')
                        ->label($ar ? 'دمج في فاتورة واحدة' : 'Merge into one invoice')
                        ->icon('heroicon-o-document-duplicate')
                        ->requiresConfirmation()
                        ->modalDescription($ar
                            ? 'سيتم نقل كل الكتب إلى أقدم فاتورة وحذف باقي الفواتير المحددة. يجب أن تكون لنفس المشتري ونفس العملة.'
                            : 'All lines move into the oldest invoice and the other selected invoices are deleted. They must share the same buyer and currency.')
                        ->action(function (Collection $records) use ($ar): void {
                            if ($records->count() < 2 || ! Invoice::sameBuyerAndCurrency($records)) {
                                static::notifySameBuyerRequired($ar);

                                return;
                            }

                            $target = Invoice::merge($records);

                            Notification::make()
                                ->title($ar ? "تم الدمج في الفاتورة رقم {$target->id}" : "Merged into invoice #{$target->id}")
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),

                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    protected static function notifySameBuyerRequired(bool $ar): void
    {
        Notification::make()
            ->title($ar ? 'تعذر التنفيذ' : 'Cannot continue')
            ->body($ar
                ? 'اختر فاتورتين أو أكثر لنفس المشتري وبنفس العملة.'
                : 'Select two or more invoices for the same buyer and currency.')
            ->danger()
            ->send();
    }
}
