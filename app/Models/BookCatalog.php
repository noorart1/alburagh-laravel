<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BookCatalog extends Model
{
    protected $guarded = [];

    // float, not the DB's "4.00" decimal string, so inline inputs show 4
    protected $casts = [
        'price_usd' => 'float',
        'price_aed' => 'float',
        'price_iqd' => 'float',
        'cost_iqd' => 'float',
    ];

    /** @return array<string, string> */
    public static function categoryOptions(): array
    {
        $ar = app()->getLocale() === 'ar';

        return [
            'series' => $ar ? 'سلسلة' : 'Series',
            'book' => $ar ? 'كتاب' : 'Book',
            'part' => $ar ? 'جزء' : 'Part',
            'game' => $ar ? 'لعبة' : 'Game',
            'game_books' => $ar ? 'كتب العاب' : 'Game Books',
            'islamic_series' => $ar ? 'سلسلة إسلامية' : 'Islamic Series',
            'islamic_books' => $ar ? 'كتاب إسلامية' : 'Islamic Book',
            'islamic_part' => $ar ? 'جزء إسلامي' : 'Islamic Part',
            'frozen' => $ar ? 'تجميد' : 'Frozen',
        ];
    }

    /** @return array<string, int> books per value of $field ('' for empty) */
    public static function countsBy(string $field): array
    {
        return static::query()->selectRaw("{$field} as k, count(*) as c")->groupBy($field)->pluck('c', 'k')->map(fn ($c) => (int) $c)->all();
    }

    /**
     * Per column, how many books have a value in it. trim() turns numbers into
     * text first, so a 0 still counts (MySQL would match 0 against '').
     *
     * @param  array<int, string>  $fields
     * @return array<string, int>
     */
    public static function filledCounts(array $fields): array
    {
        $row = static::query()->selectRaw(collect($fields)->map(fn (string $f): string => "count(nullif(trim({$f}), '')) as {$f}")->implode(', '))->toBase()->first();

        return array_map('intval', (array) $row);
    }

    /** @return array<string, string> */
    public static function publisherOptions(): array
    {
        $ar = app()->getLocale() === 'ar';

        return [
            'dar_alburagh' => $ar ? 'دار البراق لثقافة الأطفال' : 'Dar Al-Buraq for Children’s Culture',
            'dar_maheroon' => $ar ? 'دار ماهرون للنشر والتوزيع' : 'Dar Maheroon for Publishing and Distribution',
            'supplies' => $ar ? 'توريدات' : 'Supplies',
            'all_publications' => $ar ? 'جميع الإصدارات' : 'All Publications',
        ];
    }
}
