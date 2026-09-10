<?php

namespace App\Filament\Resources\Books\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class BookForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([

                Hidden::make('type')
                    ->default('book'),

                Section::make(__('admin.main_information'))
                    ->schema([

                        TextInput::make('title')
                            ->label(__('admin.book_title'))
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

                Section::make(__('admin.files'))
                    ->schema([

                        FileUpload::make('file_path')
                            ->label(__('admin.book_file'))
                            ->disk('drupal')
                            ->directory('books/files')
                            ->preserveFilenames()
                            ->acceptedFileTypes([
                                'application/zip',
                                'application/x-zip-compressed',
                            ])
                            ->downloadable()
                            ->openable()
                            ->deletable()
                            ->columnSpanFull(),

                        TextInput::make('folder')
                            ->label(__('admin.folder'))
                            ->maxLength(255),

                        FileUpload::make('main_image')
                            ->label(__('admin.cover_image'))
                            ->disk('drupal')
                            ->directory('books/covers')
                            ->preserveFilenames()
                            ->image()
                            ->imagePreviewHeight('180')
                            ->openable()
                            ->downloadable()
                            ->deletable()
                            ->columnSpanFull(),

                        TextInput::make('site_image')
                            ->label(__('admin.site_image'))
                            ->maxLength(255)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Section::make(__('admin.book_details'))
                    ->relationship('bookDetail')
                    ->schema([

                        Select::make('age_group_id')
                            ->label(__('admin.age_group'))
                            ->relationship('ageGroup', 'name')
                            ->searchable()
                            ->preload(),

                        Select::make('author_id')
                            ->label(__('admin.author'))
                            ->relationship('author', 'name')
                            ->searchable()
                            ->preload(),

                        Select::make('category_id')
                            ->label(__('admin.category'))
                            ->relationship('category', 'name')
                            ->searchable()
                            ->preload(),

                        Select::make('illustrator_id')
                            ->label(__('admin.illustrator'))
                            ->relationship('illustrator', 'name')
                            ->searchable()
                            ->preload(),

                        Select::make('publisher_id')
                            ->label(__('admin.publisher'))
                            ->relationship('publisher', 'name')
                            ->searchable()
                            ->preload(),

                        Select::make('series_id')
                            ->label(__('admin.series'))
                            ->relationship('series', 'name')
                            ->searchable()
                            ->preload(),

                        TextInput::make('ios_pid')
                            ->label('iOS PID')
                            ->maxLength(255),

                        TextInput::make('md5')
                            ->label('MD5')
                            ->maxLength(255),

                        TextInput::make('download_count')
                            ->label(__('admin.download_count'))
                            ->numeric()
                            ->default(0),

                        TextInput::make('rate')
                            ->label(__('admin.rate'))
                            ->numeric()
                            ->step(0.01)
                            ->default(0),

                        TextInput::make('rate_count')
                            ->label(__('admin.rate_count'))
                            ->numeric()
                            ->default(0),
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
                                    ->directory('books/samples')
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