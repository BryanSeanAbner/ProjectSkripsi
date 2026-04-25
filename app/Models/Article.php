<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Article extends Model
{
    protected $table      = 'article';
    protected $primaryKey = 'article_id';
    public    $timestamps = false;

    protected $fillable = [
        'article_id', 'title', 'slug', 'content',
        'photo_url', 'publish_date', 'url',
        'publish_by_id', 'author_id', 'section_id', 'view_count',
    ];

    protected function casts(): array
    {
        return [
            'publish_date' => 'datetime',
            'view_count'   => 'integer',
        ];
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class, 'section_id', 'section_id');
    }
}
