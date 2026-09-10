<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Language extends Model
{
    protected $fillable = [
        'drupal_tid',
        'name',
        'short_name',
        'is_active',
    ];

    public function contents()
    {
        return $this->hasMany(Content::class);
    }
}