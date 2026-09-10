<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BookDetail extends Model
{
    protected $fillable = [
        'content_id',
        'age_group_id',
        'author_id',
        'category_id',
        'illustrator_id',
        'publisher_id',
        'series_id',
        'cover_path',
        'ios_pid',
        'md5',
        'download_count',
        'rate',
        'rate_count',
    ];

    public function content()
    {
        return $this->belongsTo(Content::class);
    }

    public function ageGroup()
    {
        return $this->belongsTo(AgeGroup::class);
    }

    public function author()
    {
        return $this->belongsTo(Author::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function illustrator()
    {
        return $this->belongsTo(Illustrator::class);
    }

    public function publisher()
    {
        return $this->belongsTo(Publisher::class);
    }

    public function series()
    {
        return $this->belongsTo(Series::class);
    }
}