<?php

namespace App\Filament\Resources\Invoices\Schemas;

use App\Models\BookCatalog;
use Closure;
use App\Models\Invoice;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class InvoiceForm
{
    public static function configure(Schema $schema): Schema
    {
        $ar = app()->getLocale() === 'ar';

        return $schema
            ->components([
                // One headerless card: details row, item rows, total — no section titles.
                Section::make()
                    ->compact()
                    ->extraAttributes(['class' => 'invoice-form'])
                    ->schema([
                      Grid::make(3)->schema([
                        TextInput::make('buyer_name')
                            ->label($ar ? 'اسم المشتري' : 'Buyer Name')
                            ->required()
                            ->maxLength(255)
                            ->datalist(fn (): array => Invoice::query()->distinct()->orderBy('buyer_name')->pluck('buyer_name')->all()),

                        DatePicker::make('date')
                            ->label($ar ? 'التاريخ' : 'Date')
                            ->default(now())
                            ->required(),

                        Select::make('currency')
                            ->label($ar ? 'العملة (السعر حسب الاختيار)' : 'Currency (price list)')
                            ->options(Invoice::currencyOptions())
                            ->default('iqd')
                            ->required()
                            ->native(false)
                            ->live()
                            ->afterStateUpdated(function (?string $state, Get $get, Set $set): void {
                                // Re-price every line from its book in the newly chosen currency.
                                $items = $get('items') ?? [];

                                foreach ($items as $key => $item) {
                                    if ($book = BookCatalog::find($item['book_catalog_id'] ?? null)) {
                                        $items[$key]['unit_price'] = $book->{Invoice::PRICE_COLUMNS[$state] ?? 'price_iqd'};
                                    }
                                }

                                $set('items', $items);
                            }),
                      ]),

                        Repeater::make('items')
                            ->hiddenLabel()
                            ->relationship()
                            // Title is taken from the chosen book when the line is saved.
                            ->mutateRelationshipDataBeforeCreateUsing(fn (array $data): array => static::withBookTitle($data))
                            ->mutateRelationshipDataBeforeSaveUsing(fn (array $data): array => static::withBookTitle($data))
                            ->schema([
                                TextInput::make('barcode')
                                    ->label($ar ? 'باركود' : 'Barcode')
                                    ->readOnly(),

                                Select::make('book_catalog_id')
                                    ->label($ar ? 'اسم الكتاب' : 'Book Title')
                                    ->required()
                                    ->placeholder($ar ? 'بحث بالاسم أو الباركود أو الرقم' : 'Search title, barcode or #')
                                    ->searchable()
                                    ->getSearchResultsUsing(fn (string $search): array => BookCatalog::query()
                                        ->where(fn ($query) => $query
                                            ->where('title', 'like', "%{$search}%")
                                            ->orWhere('barcode', 'like', "%{$search}%")
                                            ->orWhere('catalog_number', $search))
                                        ->limit(50)
                                        ->get()
                                        ->mapWithKeys(fn (BookCatalog $book) => [$book->id => static::bookLabel($book)])
                                        ->all())
                                    ->getOptionLabelUsing(fn ($value): ?string => ($book = BookCatalog::find($value)) ? static::bookLabel($book, full: false) : null)
                                    ->live()
                                    ->afterStateUpdated(function (?string $state, Get $get, Set $set): void {
                                        $book = BookCatalog::find($state);

                                        $set('barcode', $book?->barcode);
                                        $set('unit_price', $book?->{Invoice::PRICE_COLUMNS[$get('../../currency')] ?? 'price_iqd'});
                                    }),

                                TextInput::make('unit_price')
                                    ->label($ar ? 'السعر' : 'Price')
                                    ->numeric()
                                    ->minValue(0)
                                    ->required()
                                    ->live(onBlur: true),

                                TextInput::make('quantity')
                                    ->label($ar ? 'الكمية' : 'Quantity')
                                    ->numeric()
                                    ->integer()
                                    ->minValue(1)
                                    ->default(1)
                                    ->required()
                                    ->rules([
                                        fn (Get $get, $livewire): Closure => function (string $attribute, mixed $value, Closure $fail) use ($get, $livewire, $ar): void {
                                            $book = BookCatalog::find($get('book_catalog_id'));

                                            if (! $book) {
                                                return;
                                            }

                                            // Every line of this book in the form, against its stock plus what
                                            // this invoice already took (when editing, those come back first).
                                            $wanted = collect($get('../../items'))
                                                ->where('book_catalog_id', $book->id)
                                                ->sum(fn (array $item): int => (int) ($item['quantity'] ?? 0));
                                            $held = ($livewire->record ?? null)?->items()->where('book_catalog_id', $book->id)->sum('quantity') ?? 0;
                                            $available = $book->quantity + $held;

                                            if ($wanted > $available) {
                                                $fail($ar
                                                    ? 'المخزون غير كافٍ. المتوفر: '.max(0, $available)
                                                    : 'Not enough stock. Available: '.max(0, $available));
                                            }
                                        },
                                    ])
                                    ->live(onBlur: true),

                                TextEntry::make('line_total')
                                    ->label($ar ? 'المجموع' : 'Total')
                                    ->state(fn (Get $get): string => Invoice::formatAmount((float) $get('unit_price') * (int) $get('quantity'))),
                            ])
                            ->table([
                                TableColumn::make($ar ? 'باركود' : 'Barcode')->width('14%'),
                                TableColumn::make($ar ? 'اسم الكتاب' : 'Title'),
                                TableColumn::make($ar ? 'السعر' : 'Price')->width('10%'),
                                TableColumn::make($ar ? 'الكمية' : 'Qty')->width('8%'),
                                TableColumn::make($ar ? 'المجموع' : 'Total')->width('10%'),
                            ])
                            ->addActionLabel($ar ? 'إضافة كتاب' : 'Add book')
                            ->defaultItems(1),

                        TextEntry::make('grand_total')
                            ->label($ar ? 'الإجمالي' : 'Grand Total')
                            ->inlineLabel()
                            ->weight('bold')
                            ->state(fn (Get $get): string => Invoice::formatAmount(
                                collect($get('items') ?? [])->sum(fn (array $item) => (float) ($item['unit_price'] ?? 0) * (int) ($item['quantity'] ?? 0)),
                            )),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    /** Search results show number and barcode too (searchable by them); a chosen line just its title. */
    protected static function bookLabel(BookCatalog $book, bool $full = true): string
    {
        $stock = (app()->getLocale() === 'ar' ? 'المخزون: ' : 'Stock: ').$book->quantity;
        $parts = $full ? [$book->catalog_number, $book->barcode, $book->title] : [$book->title];

        return collect($parts)->filter()->implode(' — ')." ({$stock})";
    }

    /** @param  array<string, mixed>  $data */
    protected static function withBookTitle(array $data): array
    {
        if ($book = BookCatalog::find($data['book_catalog_id'] ?? null)) {
            $data['title'] = $book->title;
        }

        return $data;
    }
}
