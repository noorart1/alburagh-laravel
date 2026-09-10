<?php

namespace App\Filament\Resources\Users\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([

                TextColumn::make('id')
                    ->label(__('admin.id'))
                    ->sortable(),

                TextColumn::make('name')
                    ->label(__('admin.name'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('username')
                    ->label(__('admin.username'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('email')
                    ->label(__('admin.email'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('fullname')
                    ->label(__('admin.fullname'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('country')
                    ->label(__('admin.country'))
                    ->searchable()
                    ->sortable(),

                ToggleColumn::make('is_active')
                    ->label(__('admin.active_user'))
                    ->disabled(fn ($record): bool => (int) $record->id === (int) auth()->id())
                    ->sortable(),

                ToggleColumn::make('is_admin')
                    ->label(__('admin.administrator'))
                    ->disabled(fn ($record): bool => (int) $record->id === (int) auth()->id())
                    ->sortable(),

                TextColumn::make('membership_date')
                    ->label(__('admin.membership_date'))
                    ->state(
                        fn ($record) => $record->drupal_created_at ?? $record->created_at
                    )
                    ->dateTime('M d, Y H:i:s')
                    ->sortable(
                        query: function ($query, string $direction) {
                            return $query->orderByRaw(
                                "COALESCE(drupal_created_at, created_at) {$direction}"
                            );
                        }
                    ),

                TextColumn::make('drupal_uid')
                    ->label(__('admin.drupal_uid'))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label(__('admin.created_at'))
                    ->dateTime('M d, Y H:i:s')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->label(__('admin.updated_at'))
                    ->dateTime('M d, Y H:i:s')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])

            ->filters([
                SelectFilter::make('is_active')
                    ->label(__('admin.active_user'))
                    ->options([
                        1 => app()->getLocale() === 'ar' ? 'فعّال' : 'Active',
                        0 => app()->getLocale() === 'ar' ? 'غير فعّال' : 'Inactive',
                    ]),

                SelectFilter::make('is_admin')
                    ->label(__('admin.administrator'))
                    ->options([
                        1 => app()->getLocale() === 'ar' ? 'مدير' : 'Admin',
                        0 => app()->getLocale() === 'ar' ? 'مستخدم' : 'User',
                    ]),
            ])

            ->defaultSort('membership_date', 'desc')

            ->persistFiltersInSession()
            ->persistSearchInSession()
            ->persistColumnSearchesInSession()
            ->persistSortInSession()

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
