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

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function bookCatalog(): BelongsTo
    {
        return $this->belongsTo(BookCatalog::class);
    }
}
