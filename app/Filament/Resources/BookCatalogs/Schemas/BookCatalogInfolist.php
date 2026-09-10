<?php

namespace App\Filament\Resources\BookCatalogs\Schemas;

use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class BookCatalogInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                Section::make(app()->getLocale() === 'ar' ? 'صورة الغلاف' : 'Cover')
                    ->schema([
                        ImageEntry::make('cover_image')
                            ->hiddenLabel()
                            ->disk('public')
                            ->height(260)
                            ->square(false),
                    ])
                    ->columnSpan(1),

                Section::make(app()->getLocale() === 'ar' ? 'المعلومات الأساسية' : 'Basic Information')
                    ->schema([

                        TextEntry::make('catalog_number')
                            ->label('#')
                            ->copyable()
                            ->placeholder('—'),

                        TextEntry::make('title')
                            ->label(app()->getLocale() === 'ar' ? 'اسم الكتاب' : 'Book Title')
                            ->weight('bold')
                            ->columnSpanFull(),

                        TextEntry::make('barcode')
                            ->label(app()->getLocale() === 'ar' ? 'باركد الكتاب' : 'Barcode')
                            ->copyable()
                            ->placeholder('—'),

                        TextEntry::make('series_name')
                            ->label(app()->getLocale() === 'ar' ? 'اسم السلسلة' : 'Series Name')
                            ->placeholder('—'),

                        TextEntry::make('author')
                            ->label(app()->getLocale() === 'ar' ? 'اسم المؤلف' : 'Author')
                            ->placeholder('—'),

                        TextEntry::make('illustrator')
                            ->label(app()->getLocale() === 'ar' ? 'الرسام' : 'Illustrator')
                            ->placeholder('—'),

                        TextEntry::make('subject')
                            ->label(app()->getLocale() === 'ar' ? 'موضوع الكتاب' : 'Subject')
                            ->placeholder('—'),

                        TextEntry::make('age_group')
                            ->label(app()->getLocale() === 'ar' ? 'الفئة العمرية' : 'Age Group')
                            ->placeholder('—'),
                    ])
                    ->columns(2)
                    ->columnSpan(1),

                Section::make(app()->getLocale() === 'ar' ? 'الطباعة والمواصفات' : 'Printing & Specifications')
                    ->schema([
                        TextEntry::make('edition_number')
                            ->label(app()->getLocale() === 'ar' ? 'رقم الطبعة' : 'Edition Number')
                            ->placeholder('—'),

                        TextEntry::make('print_place')
                            ->label(app()->getLocale() === 'ar' ? 'مكان الطباعة' : 'Print Place')
                            ->placeholder('—'),

                        TextEntry::make('print_year')
                            ->label(app()->getLocale() === 'ar' ? 'سنة الطباعة' : 'Print Year')
                            ->placeholder('—'),

                        TextEntry::make('pages')
                            ->label(app()->getLocale() === 'ar' ? 'عدد الصفحات' : 'Pages')
                            ->placeholder('—'),

                        TextEntry::make('size')
                            ->label(app()->getLocale() === 'ar' ? 'القياس (سنتيمتر)' : 'Size (cm)')
                            ->placeholder('—'),

                        TextEntry::make('weight')
                            ->label(app()->getLocale() === 'ar' ? 'الوزن (كيلوغرام)' : 'Weight (kg)')
                            ->placeholder('—'),
                    ])
                    ->columns(3),

                Section::make(app()->getLocale() === 'ar' ? 'الأسعار' : 'Prices')
                    ->schema([
                        TextEntry::make('price_usd')
                            ->label(app()->getLocale() === 'ar' ? 'السعر بالدولار' : 'USD')
                            ->formatStateUsing(fn ($state) => $state === null ? '—' : number_format((float) $state, 2)),

                        TextEntry::make('price_aed')
                            ->label(app()->getLocale() === 'ar' ? 'بالدرهم الإماراتي' : 'AED')
                            ->formatStateUsing(fn ($state) => $state === null ? '—' : number_format((float) $state, 2)),

                        TextEntry::make('price_iqd')
                            ->label(app()->getLocale() === 'ar' ? 'بالدينار العراقي' : 'IQD')
                            ->formatStateUsing(fn ($state) => $state === null ? '—' : number_format((float) $state, 0)),
                    ])
                    ->columns(3),

                Section::make(app()->getLocale() === 'ar' ? 'الإيداع' : 'Deposit')
                    ->schema([
                        TextEntry::make('deposit_number')
                            ->label(app()->getLocale() === 'ar' ? 'رقم الإيداع' : 'Deposit Number')
                            ->placeholder('—'),

                        TextEntry::make('deposit_year')
                            ->label(app()->getLocale() === 'ar' ? 'سنة الإيداع' : 'Deposit Year')
                            ->placeholder('—'),
                    ])
                    ->columns(3),

                Section::make(app()->getLocale() === 'ar' ? 'الوصف' : 'Description')
                    ->schema([
                        TextEntry::make('short_description')
                            ->label(app()->getLocale() === 'ar' ? 'نبذة مختصرة عن الكتاب' : 'Short Description')
                            ->placeholder('—')
                            ->columnSpanFull(),

                        TextEntry::make('notes')
                            ->label(app()->getLocale() === 'ar' ? 'ملاحظات' : 'Notes')
                            ->placeholder('—')
                            ->columnSpanFull(),
                    ])
                    ->columnSpanFull(),

                Section::make(app()->getLocale() === 'ar' ? 'بيانات النظام' : 'System Information')
                    ->schema([
                        TextEntry::make('created_at')
                            ->label(app()->getLocale() === 'ar' ? 'تاريخ الإدخال' : 'Created At')
                            ->dateTime()
                            ->placeholder('—'),

                        TextEntry::make('updated_at')
                            ->label(app()->getLocale() === 'ar' ? 'آخر تحديث' : 'Updated At')
                            ->dateTime()
                            ->placeholder('—'),
                    ])
                    ->columns(2)
                    ->collapsed()
                    ->columnSpanFull(),
            ]);
    }
}
