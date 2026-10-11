<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceItem extends Model
{
    protected $guarded = [];

    // float, not the DB's "12200.00" decimal string, so inputs show 12200
    protected $casts = [
        'unit_price' => 'float',
    ];

    /** Invoice lines take their quantity out of the book's stock (and give it back when removed). */
    protected static function booted(): void
    {
        static::created(fn (self $item) => static::moveStock($item->book_catalog_id, -$item->quantity));

        static::updated(function (self $item): void {
            if ($item->wasChanged(['book_catalog_id', 'quantity'])) {
                static::moveStock($item->getOriginal('book_catalog_id'), (int) $item->getOriginal('quantity'));
                static::moveStock($item->book_catalog_id, -$item->quantity);
            }
        });

        static::deleted(fn (self $item) => static::moveStock($item->book_catalog_id, $item->quantity));
    }

    protected static function moveStock(?int $bookId, int $by): void
    {
        if ($bookId && $by) {
            BookCatalog::whereKey($bookId)->increment('quantity', $by);
        }
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function bookCatalog(): BelongsTo
    {
        return $this->belongsTo(BookCatalog::class);
    }
}
