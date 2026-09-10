<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Publisher extends Model
{
    protected $fillable = [
        'drupal_tid',
        'name',
    ];

    public function bookDetails()
    {
        return $this->hasMany(BookDetail::class);
    }
}