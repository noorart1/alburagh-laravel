<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Guide extends Model
{
    protected $fillable = [
        'drupal_nid',
        'title',
        'body',
        'sort_order',
        'is_published',
    ];

    public function images()
    {
        return $this->hasMany(GuideImage::class)
            ->orderBy('sort_order');
    }
}