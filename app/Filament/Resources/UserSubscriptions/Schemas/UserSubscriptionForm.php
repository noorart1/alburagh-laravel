<?php

namespace App\Filament\Resources\UserSubscriptions\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class UserSubscriptionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([

                Section::make(__('admin.subscription_information'))
                    ->schema([

                        Select::make('user_id')
                            ->label(__('admin.user'))
                            ->relationship('user', 'email')
                            ->getOptionLabelFromRecordUsing(
                                fn ($record): string =>
                                    $record->email
                                    ?: $record->username
                                    ?: ('User #' . $record->id)
                            )
                            ->searchable()
                            ->preload()
                            ->required(),

                        Select::make('subscription_code_id')
                            ->label(__('admin.subscription_code'))
                            ->relationship('subscriptionCode', 'code')
                            ->getOptionLabelFromRecordUsing(
                                fn ($record): string =>
                                    $record->code
                                    ?: $record->serial
                                    ?: ('Code #' . $record->id)
                            )
                            ->searchable()
                            ->preload(),
                        TextInput::make('amount')
                            ->label(__('admin.amount'))
                            ->numeric(),

                        TextInput::make('type')
                            ->label(__('admin.subscription_type'))
                            ->maxLength(255),

                        DateTimePicker::make('subscription_date')
                            ->label(__('admin.subscription_date')),

                        TextInput::make('drupal_nid')
                            ->label(__('admin.drupal_nid'))
                            ->disabled(),
                    ])
                    ->columns(2),
            ]);
    }
}