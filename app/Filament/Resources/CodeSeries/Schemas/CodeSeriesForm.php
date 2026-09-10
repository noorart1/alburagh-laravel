<?php

namespace App\Filament\Resources\CodeSeries\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CodeSeriesForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([

                Section::make('اطلاعات سری کد')
                    ->schema([

                        TextInput::make('prefix')
                            ->label('پیشوند')
                            ->maxLength(255),

                        TextInput::make('country')
                            ->label('کشور')
                            ->maxLength(10),

                        TextInput::make('series_start')
                            ->label('شروع سری')
                            ->numeric(),

                        TextInput::make('series_end')
                            ->label('پایان سری')
                            ->numeric(),

                        TextInput::make('drupal_nid')
                            ->label('Drupal NID')
                            ->disabled(),
                    ])
                    ->columns(2),
            ]);
    }
}