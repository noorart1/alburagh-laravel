<?php

namespace App\Filament\Resources\Contents\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class ContentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('drupal_nid')
                    ->numeric()
                    ->default(null),
                Select::make('type')
                    ->options(['book' => 'Book', 'game' => 'Game', 'quran' => 'Quran', 'sound_book' => 'Sound book'])
                    ->required(),
                TextInput::make('title')
                    ->required(),
                Textarea::make('description')
                    ->default(null)
                    ->columnSpanFull(),
                TextInput::make('language_id')
                    ->numeric()
                    ->default(null),
                TextInput::make('file_path')
                    ->default(null),
                TextInput::make('folder')
                    ->default(null),
                FileUpload::make('main_image')
                    ->image(),
                FileUpload::make('site_image')
                    ->image(),
                TextInput::make('sort_order')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('reading_count')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('version')
                    ->default(null),
                TextInput::make('content_version')
                    ->required()
                    ->numeric()
                    ->default(0),
                Toggle::make('is_published')
                    ->required(),
            ]);
    }
}
