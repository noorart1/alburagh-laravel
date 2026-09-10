<?php

namespace App\Filament\Resources\UserSubscriptions\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class UserSubscriptionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([

                TextColumn::make('user.email')
                    ->label(__('admin.user_email'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('user.username')
                    ->label(__('admin.username'))
                    ->searchable(),

                TextColumn::make('subscriptionCode.code')
                    ->label(__('admin.subscription_code'))
                    ->searchable()
                    ->sortable()
                    ->placeholder('-'),

                TextColumn::make('amount')
                    ->label(__('admin.amount'))
                    ->sortable(),

                TextColumn::make('type')
                    ->label(__('admin.subscription_type'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('subscription_date')
                    ->label(__('admin.subscription_date'))
                    ->dateTime()
                    ->sortable(),

                TextColumn::make('drupal_nid')
                    ->label(__('admin.drupal_nid'))
                    ->toggleable(isToggledHiddenByDefault: true),
            ])

            ->defaultSort('subscription_date', 'desc')

            ->recordActions([
                EditAction::make(),
            ])

            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}