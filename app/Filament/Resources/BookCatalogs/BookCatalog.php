<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BookCatalog extends Model
{
    protected $fillable = [
        'barcode',
        'series_name',
        'title',
        'author',
        'illustrator',
        'subject',
        'edition_number',
        'print_place',
        'print_year',
        'short_description',
        'pages',
        'price_usd',
        'price_aed',
        'price_iqd',
        'deposit_number',
        'wrong_deposit_number',
        'deposit_year',
        'size',
        'weight',
        'age_group',
        'cover_image',
        'notes',
    ];

    protected $casts = [
        'edition_number' => 'integer',
        'print_year' => 'integer',
        'pages' => 'integer',
        'price_usd' => 'decimal:2',
        'price_aed' => 'decimal:2',
        'price_iqd' => 'decimal:0',
        'deposit_year' => 'integer',
        'weight' => 'decimal:3',
    ];
}
