<?php

namespace App\Filament\Resources\Guides\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class GuideForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([

                Section::make('اطلاعات راهنما')
                    ->schema([

                        TextInput::make('title')
                            ->label('عنوان')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),

                        RichEditor::make('body')
                            ->label('متن راهنما')
                            ->columnSpanFull(),

                        TextInput::make('sort_order')
                            ->label('ترتیب')
                            ->numeric()
                            ->default(0),

                        Toggle::make('is_published')
                            ->label('منتشرشده')
                            ->default(true),

                        TextInput::make('drupal_nid')
                            ->label('Drupal NID')
                            ->disabled(),
                    ])
                    ->columns(2),

                Section::make('تصاویر راهنما')
                    ->schema([

                        Repeater::make('images')
                            ->relationship()
                            ->schema([

                                FileUpload::make('image_path')
                                    ->label('تصویر')
                                    ->disk('drupal')
                                    ->directory('guide')
                                    ->preserveFilenames()
                                    ->image()
                                    ->imagePreviewHeight('160')
                                    ->openable()
                                    ->downloadable()
                                    ->deletable()
                                    ->required(),

                            ])
                            ->orderColumn('sort_order')
                            ->reorderable()
                            ->addActionLabel('افزودن تصویر')
                            ->defaultItems(0)
                            ->columnSpanFull(),

                    ]),

            ]);
    }
}