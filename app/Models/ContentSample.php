<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContentSample extends Model
{
    protected $fillable = [
        'content_id',
        'image_path',
        'sort_order',
    ];

    public function content()
    {
        return $this->belongsTo(Content::class);
    }
}