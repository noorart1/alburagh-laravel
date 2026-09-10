<?php

namespace App\Filament\Resources\SoundBooks\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SoundBookForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([

                Hidden::make('type')
                    ->default('sound_book'),

                Section::make(__('admin.main_information'))
                    ->schema([

                        TextInput::make('title')
                            ->label(__('admin.sound_book_title'))
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),

                        Textarea::make('description')
                            ->label(__('admin.description'))
                            ->rows(6)
                            ->columnSpanFull(),

                        Select::make('language_id')
                            ->label(__('admin.language'))
                            ->relationship('language', 'name')
                            ->searchable()
                            ->preload(),

                        TextInput::make('sort_order')
                            ->label(__('admin.sort_order'))
                            ->numeric()
                            ->default(0),

                        TextInput::make('reading_count')
                            ->label(__('admin.reading_count'))
                            ->numeric()
                            ->default(0),

                        TextInput::make('version')
                            ->label(__('admin.version'))
                            ->maxLength(255),

                        TextInput::make('content_version')
                            ->label(__('admin.content_version'))
                            ->numeric()
                            ->default(0),

                        Toggle::make('is_published')
                            ->label(__('admin.is_published'))
                            ->default(true),
                    ])
                    ->columns(2),

                Section::make(__('admin.files_and_images'))
                    ->schema([

                        FileUpload::make('file_path')
                            ->label(__('admin.sound_book_file'))
                            ->disk('drupal')
                            ->directory('sound_books/files')
                            ->preserveFilenames()
                            ->downloadable()
                            ->openable()
                            ->deletable()
                            ->columnSpanFull(),

                        TextInput::make('folder')
                            ->label(__('admin.folder'))
                            ->maxLength(255),

                        FileUpload::make('main_image')
                            ->label(__('admin.main_image'))
                            ->disk('drupal')
                            ->directory('sound_books/images')
                            ->preserveFilenames()
                            ->image()
                            ->imagePreviewHeight('180')
                            ->openable()
                            ->downloadable()
                            ->deletable()
                            ->columnSpanFull(),

                        FileUpload::make('site_image')
                            ->label(__('admin.site_image'))
                            ->disk('drupal')
                            ->directory('sound_books/site_images')
                            ->preserveFilenames()
                            ->image()
                            ->imagePreviewHeight('180')
                            ->openable()
                            ->downloadable()
                            ->deletable()
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Section::make(__('admin.sample_images'))
                    ->schema([

                        Repeater::make('samples')
                            ->relationship()
                            ->schema([

                                FileUpload::make('image_path')
                                    ->label(__('admin.sample_image'))
                                    ->disk('drupal')
                                    ->directory('sound_books/samples')
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
                            ->addActionLabel(__('admin.add_sample_image'))
                            ->defaultItems(0)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}