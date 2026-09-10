<?php

namespace App\Filament\Resources\BookCatalogs\Schemas;

use App\Models\BookCatalog;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class BookCatalogForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(2)
                    ->schema([
                        // ستون راست
                        Grid::make(1)
                            ->schema([
                                Section::make(app()->getLocale() === 'ar' ? 'المعلومات الأساسية' : 'Basic Information')
                                    ->schema([
                                        TextInput::make('catalog_number')
                                            ->label('#')
                                            ->numeric()
                                            ->required()
                                            ->minValue(1000)
                                            ->default(fn (): int => max(
                                                1000,
                                                ((int) BookCatalog::query()->max('catalog_number')) + 1
                                            ))
                                            ->unique(
                                                table: BookCatalog::class,
                                                column: 'catalog_number',
                                                ignoreRecord: true,
                                            )
                                            ->helperText(
                                                app()->getLocale() === 'ar'
                                                    ? 'رقم فريد يبدأ من 1000 ويمكن تعديله.'
                                                    : 'Unique number starting from 1000. Editable.'
                                            ),

                                        TextInput::make('temporary_catalog_number')
                                            ->label(app()->getLocale() === 'ar' ? 'رقم مؤقت للعرض' : 'Temporary Display #')
                                            ->maxLength(20)
                                            ->extraInputAttributes([
                                                'dir' => 'ltr',
                                                'style' => 'text-align:left;',
                                            ])
                                            ->placeholder('1010+')
                                            ->helperText(
                                                app()->getLocale() === 'ar'
                                                    ? 'اختياري. مثال: 1010+ ليظهر مباشرة بعد 1010 في الجدول.'
                                                    : 'Optional. Example: 1010+ to display directly after 1010 in the table.'
                                            ),

                                        Select::make('category')
                                            ->label(app()->getLocale() === 'ar' ? 'الصنف' : 'Category')
                                            ->options([
                                                'book' => app()->getLocale() === 'ar' ? 'كتاب' : 'Book',
                                                'game' => app()->getLocale() === 'ar' ? 'لعبة' : 'Game',
                                                'islamic' => app()->getLocale() === 'ar' ? 'إسلامي' : 'Islamic',
                                            ])
                                            ->native(false)
                                            ->searchable()
                                            ->required(),

                                        Select::make('publisher')
                                            ->label(app()->getLocale() === 'ar' ? 'الناشر' : 'Publisher')
                                            ->options([
                                                'dar_alburagh' => app()->getLocale() === 'ar'
                                                    ? 'دار البراق لثقافة الأطفال'
                                                    : 'Dar Al-Buraq for Children’s Culture',
                                                'dar_maheroon' => app()->getLocale() === 'ar'
                                                    ? 'دار ماهرون للنشر والتوزيع'
                                                    : 'Dar Maheroon for Publishing and Distribution',
                                                'supplies' => app()->getLocale() === 'ar'
                                                    ? 'توريدات'
                                                    : 'Supplies',
                                            ])
                                            ->native(false)
                                            ->searchable(),

                                        TextInput::make('barcode')
                                            ->label(app()->getLocale() === 'ar' ? 'باركود الكتاب' : 'Barcode')
                                            ->maxLength(50),

                                        TextInput::make('series_name')
                                            ->label(app()->getLocale() === 'ar' ? 'اسم السلسلة' : 'Series Name')
                                            ->maxLength(255),

                                        TextInput::make('title')
                                            ->label(app()->getLocale() === 'ar' ? 'اسم الكتاب' : 'Book Title')
                                            ->required()
                                            ->maxLength(255),

                                        TextInput::make('author')
                                            ->label(app()->getLocale() === 'ar' ? 'اسم المؤلف' : 'Author')
                                            ->maxLength(255),

                                        TextInput::make('illustrator')
                                            ->label(app()->getLocale() === 'ar' ? 'الرسام' : 'Illustrator')
                                            ->maxLength(255),

                                        TextInput::make('subject')
                                            ->label(app()->getLocale() === 'ar' ? 'موضوع الكتاب' : 'Subject')
                                            ->maxLength(255),

                                        TextInput::make('age_group')
                                            ->label(app()->getLocale() === 'ar' ? 'الفئة العمرية' : 'Age Group')
                                            ->maxLength(100),
                                    ])
                                    ->columns(2),

                                Section::make(app()->getLocale() === 'ar' ? 'الأسعار' : 'Prices')
                                    ->schema([
                                        TextInput::make('price_usd')
                                            ->label(app()->getLocale() === 'ar' ? 'السعر بالدولار' : 'Price USD')
                                            ->numeric()
                                            ->step('0.01')
                                            ->minValue(0),

                                        TextInput::make('price_aed')
                                            ->label(app()->getLocale() === 'ar' ? 'بالدرهم الإماراتي' : 'Price AED')
                                            ->numeric()
                                            ->step('0.01')
                                            ->minValue(0),

                                        TextInput::make('price_iqd')
                                            ->label(app()->getLocale() === 'ar' ? 'بالدينار العراقي' : 'Price IQD')
                                            ->numeric()
                                            ->step('1')
                                            ->minValue(0),
                                    ])
                                    ->columns(3),

                                Section::make(app()->getLocale() === 'ar' ? 'الوصف والغلاف' : 'Description & Cover')
                                    ->schema([
                                        Textarea::make('short_description')
                                            ->label(app()->getLocale() === 'ar' ? 'نبذة مختصرة عن الكتاب' : 'Short Description')
                                            ->rows(3)
                                            ->columnSpanFull(),

                                        FileUpload::make('cover_image')
                                            ->label(app()->getLocale() === 'ar' ? 'صورة الغلاف' : 'Cover Image')
                                            ->image()
                                            ->imageEditor()
                                            ->disk('public')
                                            ->directory('book-catalog/covers')
                                            ->visibility('public')
                                            ->maxSize(5120)
                                            ->downloadable()
                                            ->openable()
                                            ->columnSpanFull(),
                                    ]),
                            ]),

                        // ستون چپ
                        Grid::make(1)
                            ->schema([
                                Section::make(app()->getLocale() === 'ar' ? 'الطباعة والمواصفات' : 'Printing & Specifications')
                                    ->schema([
                                        TextInput::make('edition_number')
                                            ->label(app()->getLocale() === 'ar' ? 'رقم الطبعة' : 'Edition Number')
                                            ->numeric()
                                            ->minValue(0),

                                        TextInput::make('print_place')
                                            ->label(app()->getLocale() === 'ar' ? 'مكان الطباعة' : 'Print Place')
                                            ->maxLength(255),

                                        TextInput::make('print_year')
                                            ->label(app()->getLocale() === 'ar' ? 'تاريخ / سنة الطباعة' : 'Print Year')
                                            ->numeric()
                                            ->minValue(1000)
                                            ->maxValue(9999),

                                        TextInput::make('pages')
                                            ->label(app()->getLocale() === 'ar' ? 'عدد الصفحات' : 'Pages')
                                            ->numeric()
                                            ->minValue(0),

                                        TextInput::make('size')
                                            ->label(app()->getLocale() === 'ar' ? 'القياس (سنتيمتر)' : 'Size (cm)')
                                            ->maxLength(100),

                                        TextInput::make('weight')
                                            ->label(app()->getLocale() === 'ar' ? 'الوزن (كيلوغرام)' : 'Weight (kg)')
                                            ->numeric()
                                            ->step('0.001')
                                            ->minValue(0),
                                    ])
                                    ->columns(3),

                                Section::make(app()->getLocale() === 'ar' ? 'الإيداع' : 'Deposit')
                                    ->schema([
                                        TextInput::make('deposit_number')
                                            ->label(app()->getLocale() === 'ar' ? 'رقم الإيداع' : 'Deposit Number')
                                            ->maxLength(100),

                                        TextInput::make('deposit_year')
                                            ->label(app()->getLocale() === 'ar' ? 'سنة الإيداع' : 'Deposit Year')
                                            ->numeric()
                                            ->minValue(1000)
                                            ->maxValue(9999),
                                    ])
                                    ->columns(2),
                            ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}
