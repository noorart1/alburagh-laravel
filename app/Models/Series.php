<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Series extends Model
{
    protected $fillable = [
        'drupal_tid',
        'name',
        'sort_order',
        'is_active',
    ];

    public function bookDetails()
    {
        return $this->hasMany(BookDetail::class);
    }
}