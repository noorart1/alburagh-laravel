<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([

                Section::make(__('admin.user_information'))
                    ->schema([

                        TextInput::make('name')
                            ->label(__('admin.name'))
                            ->maxLength(255),

                        TextInput::make('username')
                            ->label(__('admin.username'))
                            ->maxLength(60),

                        TextInput::make('email')
                            ->label(__('admin.email'))
                            ->email()
                            ->maxLength(255),

                        TextInput::make('fullname')
                            ->label(__('admin.fullname'))
                            ->maxLength(255),

                        TextInput::make('country')
                            ->label(__('admin.country'))
                            ->maxLength(2),

                        Toggle::make('is_active')
                            ->label(__('admin.active_user'))
                            ->default(true),

                        Toggle::make('is_admin')
                            ->label(__('admin.administrator'))
                            ->helperText(__('admin.administrator_help'))
                            ->default(false),
                    ])
                    ->columns(2),

                Section::make(__('admin.change_password'))
                    ->schema([

                        TextInput::make('password')
                            ->label(__('admin.new_password'))
                            ->password()
                            ->revealable()
                            ->confirmed()
                            ->dehydrated(
                                fn ($state) => filled($state)
                            )
                            ->helperText(
                                __('admin.leave_password_blank')
                            ),

                        TextInput::make('password_confirmation')
                            ->label(__('admin.confirm_password'))
                            ->password()
                            ->revealable()
                            ->dehydrated(false),
                    ])
                    ->columns(2),

                Section::make(__('admin.migration_information'))
                    ->schema([

                        TextInput::make('drupal_uid')
                            ->label(__('admin.drupal_uid'))
                            ->disabled(),

                        TextInput::make('drupal_created_at')
                            ->label(__('admin.drupal_created_at'))
                            ->disabled(),

                        TextInput::make('drupal_last_access_at')
                            ->label(__('admin.drupal_last_access_at'))
                            ->disabled(),

                        TextInput::make('drupal_last_login_at')
                            ->label(__('admin.drupal_last_login_at'))
                            ->disabled(),
                    ])
                    ->columns(2),
            ]);
    }
}