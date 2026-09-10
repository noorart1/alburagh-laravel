<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Content extends Model
{
    protected $fillable = [
        'drupal_nid',
        'type',
        'title',
        'description',
        'language_id',
        'file_path',
        'folder',
        'main_image',
        'site_image',
        'sort_order',
        'reading_count',
        'version',
        'content_version',
        'is_published',
    ];
    protected static function booted(): void
    {
        static::saved(function (Content $content) {
            if (
                $content->type === 'book' &&
                $content->bookDetail &&
                $content->main_image
            ) {
                if ($content->bookDetail->cover_path !== $content->main_image) {
                    $content->bookDetail->update([
                        'cover_path' => $content->main_image,
                    ]);
                }
            }
        });
    }
    
    public function readers()
    {
        return $this->belongsToMany(
            User::class,
            'user_read_books',
            'content_id',
            'user_id'
        )->withTimestamps();
    }
    public function language()
    {
        return $this->belongsTo(Language::class);
    }

    public function bookDetail()
    {
        return $this->hasOne(BookDetail::class);
    }

    public function samples()
    {
        return $this->hasMany(ContentSample::class)
            ->orderBy('sort_order');
    }
}