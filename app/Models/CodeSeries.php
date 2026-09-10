<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CodeSeries extends Model
{
    protected $fillable = [
        'drupal_nid',
        'country',
        'series_start',
        'series_end',
        'prefix',
    ];

    public function codes()
    {
        return $this->hasMany(SubscriptionCode::class);
    }
}