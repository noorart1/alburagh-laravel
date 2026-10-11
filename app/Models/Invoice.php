<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
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
        'cost_iqd' => 'cost_iqd',
    ];

    /** @return array<string, string> */
    public static function currencyOptions(): array
    {
        $ar = app()->getLocale() === 'ar';

        return [
            'iqd' => $ar ? 'دينار' : 'IQD',
            'usd' => $ar ? 'دولار' : 'USD',
            'aed' => $ar ? 'درهم' : 'AED',
            'cost_iqd' => $ar ? 'الكلفة دينار' : 'Cost IQD',
        ];
    }

    /** 12200 → "12,200", 4.5 → "4.50": decimals only when there is a fraction. */
    public static function formatAmount(mixed $amount): string
    {
        $amount = (float) $amount;

        return number_format($amount, fmod($amount, 1) == 0.0 ? 0 : 2);
    }

    protected static function booted(): void
    {
        // Through the model (not the DB cascade) so each line returns its stock.
        static::deleting(fn (self $invoice) => $invoice->items()->get()->each->delete());
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    /**
     * Moves every line into the oldest invoice and deletes the rest.
     * Caller guarantees same buyer and currency.
     *
     * @param  Collection<int, Invoice>  $invoices
     */
    public static function merge(Collection $invoices): self
    {
        $target = $invoices->sortBy([['date', 'asc'], ['id', 'asc']])->first();
        $others = $invoices->pluck('id')->reject(fn ($id) => $id === $target->id);

        DB::transaction(function () use ($target, $others): void {
            InvoiceItem::whereIn('invoice_id', $others)->update(['invoice_id' => $target->id]);
            static::whereIn('id', $others)->delete();
            $target->recalculateTotal();
        });

        return $target->refresh();
    }

    /** @param  Collection<int, Invoice>  $invoices */
    public static function sameBuyerAndCurrency(Collection $invoices): bool
    {
        return $invoices->isNotEmpty()
            && $invoices->map(fn (Invoice $invoice) => trim($invoice->buyer_name))->unique()->count() === 1
            && $invoices->pluck('currency')->unique()->count() === 1;
    }

    public function recalculateTotal(): void
    {
        $this->update([
            'total' => $this->items()->sum(DB::raw('unit_price * quantity')),
        ]);
    }
}
