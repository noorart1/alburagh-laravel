<?php

namespace App\Filament\Resources\SubscriptionCodes\Tables;

use Carbon\Carbon;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class SubscriptionCodesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([

                TextColumn::make('code')
                    ->label(__('admin.code'))
                    ->searchable()
                    ->sortable()
                    ->copyable(),

                TextColumn::make('serial')
                    ->label(__('admin.serial'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('amount')
                    ->label(__('admin.amount'))
                    ->formatStateUsing(fn ($state) => match ((int) $state) {
                        0 => __('admin.none'),
                        30 => __('admin.days_30'),
                        62 => __('admin.days_62'),
                        90 => __('admin.days_90'),
                        365 => __('admin.days_365'),
                        default => $state,
                    })
                    ->sortable(),

                TextColumn::make('status')
                    ->label(__('admin.status'))
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'generated' => __('admin.status_generated'),
                        'printed' => __('admin.status_printed'),
                        'used' => __('admin.status_used'),
                        default => $state,
                    })
                    ->searchable()
                    ->sortable()
                    ->badge(),

                TextColumn::make('used_at')
                    ->label(__('admin.used_at'))
                    ->state(fn ($record) =>
                        $record->status === 'used'
                            ? $record->used_at
                            : null
                    )
                    ->dateTime('Y-m-d H:i')
                    ->placeholder('—')
                    ->sortable(),

                TextColumn::make('userSubscription.expires_at')
                    ->label(__('admin.expires_at'))
                    ->state(fn ($record) =>
                        $record->status === 'used'
                            ? $record->userSubscription?->expires_at
                            : null
                    )
                    ->dateTime('Y-m-d H:i')
                    ->placeholder('—'),

                TextColumn::make('expiry_status')
                    ->label(__('admin.expiry_status'))
                    ->state(function ($record) {
                        if ($record->status !== 'used') {
                            return 'unused';
                        }

                        $expiresAt = $record->userSubscription?->expires_at;

                        if (! $expiresAt) {
                            return 'unknown';
                        }

                        $expiresAt = $expiresAt instanceof Carbon
                            ? $expiresAt
                            : Carbon::parse($expiresAt);

                        return $expiresAt->isPast()
                            ? 'expired'
                            : 'active';
                    })
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'active' => __('admin.active_subscription'),
                        'expired' => __('admin.expired_subscription'),
                        'unknown' => __('admin.unknown_subscription'),
                        'unused' => '—',
                        default => '—',
                    })
                    ->color(fn ($state) => match ($state) {
                        'active' => 'success',
                        'expired' => 'danger',
                        'unknown' => 'warning',
                        default => 'gray',
                    })
                    ->badge(),

                TextColumn::make('series.prefix')
                    ->label(__('admin.code_series'))
                    ->sortable(),

                TextColumn::make('drupal_nid')
                    ->label(__('admin.drupal_nid'))
                    ->toggleable(isToggledHiddenByDefault: true),
            ])

            ->filters([

                Filter::make('unresolved_used')
                    ->label(__('admin.unresolved_used_codes'))
                    ->query(
                        fn ($query) => $query
                            ->where('status', 'used')
                            ->whereDoesntHave('userSubscription')
                    ),

                SelectFilter::make('expiry_status')
                    ->label(__('admin.expiry_status'))
                    ->options([
                        'active' => __('admin.active_subscription'),
                        'expired' => __('admin.expired_subscription'),
                        'unknown' => __('admin.unknown_subscription'),
                        'unused' => __('admin.unused_subscription'),
                    ])
                    ->query(function ($query, array $data) {
                        $value = $data['value'] ?? null;

                        if (! $value) {
                            return $query;
                        }

                        return match ($value) {
                            'active' => $query
                                ->where('status', 'used')
                                ->whereHas(
                                    'userSubscription',
                                    fn ($q) => $q
                                        ->whereNotNull('expires_at')
                                        ->where('expires_at', '>=', now())
                                ),

                            'expired' => $query
                                ->where('status', 'used')
                                ->whereHas(
                                    'userSubscription',
                                    fn ($q) => $q
                                        ->whereNotNull('expires_at')
                                        ->where('expires_at', '<', now())
                                ),

                            'unknown' => $query
                                ->where('status', 'used')
                                ->where(function ($q) {
                                    $q
                                        ->whereDoesntHave('userSubscription')
                                        ->orWhereHas(
                                            'userSubscription',
                                            fn ($sub) => $sub->whereNull('expires_at')
                                        );
                                }),

                            'unused' => $query
                                ->where('status', '!=', 'used'),

                            default => $query,
                        };
                    }),
            ])

            // Keep the user's list state after opening Edit and returning.
            ->persistFiltersInSession()
            ->persistSearchInSession()
            ->persistColumnSearchesInSession()
            ->persistSortInSession()

            ->defaultSort('id', 'desc')

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
