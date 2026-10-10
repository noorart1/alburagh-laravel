<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class Invoice extends Model
{
    protected $guarded = [];

    protected $casts = [
        'date' => 'date',
    ];

    /** Currency key => price column on BookCatalog. */
    public const PRICE_COLUMNS = [
        'iqd' => 'price_iqd',
        'usd' => 'price_usd',
        'aed' => 'price_aed',
    ];

    /** @return array<string, string> */
    public static function currencyOptions(): array
    {
        $ar = app()->getLocale() === 'ar';

        return [
            'iqd' => $ar ? 'دينار' : 'IQD',
            'usd' => $ar ? 'دولار' : 'USD',
            'aed' => $ar ? 'درهم' : 'AED',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function recalculateTotal(): void
    {
        $this->update([
            'total' => $this->items()->sum(DB::raw('unit_price * quantity')),
        ]);
    }
}
