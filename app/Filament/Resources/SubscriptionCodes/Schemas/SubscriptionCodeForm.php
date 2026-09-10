<?php

namespace App\Filament\Resources\SubscriptionCodes\Schemas;

use Carbon\Carbon;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SubscriptionCodeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([

                Section::make(__('admin.subscription_code_information'))
                    ->schema([

                        TextInput::make('code')
                            ->label(__('admin.code'))
                            ->maxLength(255),

                        TextInput::make('serial')
                            ->label(__('admin.serial'))
                            ->maxLength(255),

                        Select::make('status')
                            ->label(__('admin.status'))
                            ->options([
                                'generated' => __('admin.status_generated'),
                                'printed' => __('admin.status_printed'),
                                'used' => __('admin.status_used'),
                            ])
                            ->required(),

                        Select::make('amount')
                            ->label(__('admin.amount'))
                            ->options([
                                0 => __('admin.none'),
                                30 => __('admin.days_30'),
                                62 => __('admin.days_62'),
                                90 => __('admin.days_90'),
                                365 => __('admin.days_365'),
                            ])
                            ->required(),

                        Select::make('code_series_id')
                            ->label(__('admin.code_series'))
                            ->relationship('series', 'prefix')
                            ->searchable()
                            ->preload(),

                        TextInput::make('drupal_nid')
                            ->label(__('admin.drupal_nid'))
                            ->disabled(),

                        TextEntry::make('used_at_display')
                            ->label(__('admin.used_at'))
                            ->state(fn ($record) =>
                                $record?->status === 'used' && $record?->used_at
                                    ? Carbon::parse($record->used_at)->format('Y-m-d H:i')
                                    : '—'
                            ),

                        TextEntry::make('expires_at_display')
                            ->label(__('admin.expires_at'))
                            ->state(fn ($record) =>
                                $record?->status === 'used' && $record?->userSubscription?->expires_at
                                    ? Carbon::parse($record->userSubscription->expires_at)->format('Y-m-d H:i')
                                    : '—'
                            ),

                        TextEntry::make('expiry_status_display')
                            ->label(__('admin.expiry_status'))
                            ->state(function ($record) {
                                if (! $record || $record->status !== 'used') {
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
                    ])
                    ->columns(2),
            ]);
    }
}
