<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BookCatalog extends Model
{
    protected $guarded = [];

    /** @return array<string, string> */
    public static function categoryOptions(): array
    {
        $ar = app()->getLocale() === 'ar';

        return [
            'series' => $ar ? 'سلسلة' : 'Series',
            'book' => $ar ? 'كتاب' : 'Book',
            'part' => $ar ? 'جزء' : 'Part',
            'game' => $ar ? 'لعبة' : 'Game',
            'islamic' => $ar ? 'إسلامي' : 'Islamic',
        ];
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
