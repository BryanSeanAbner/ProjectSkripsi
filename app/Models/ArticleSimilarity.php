<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ArticleSimilarity extends Model
{
    protected $table      = 'article_similarity';
    protected $primaryKey = 'similarity_id';
    public    $timestamps = false;

    protected $fillable = [
        'article_id', 'similar_article_id', 'rank_position', 'generated_at',
    ];

    protected function casts(): array
    {
        return [
            'generated_at'  => 'datetime',
            'rank_position' => 'integer',
        ];
    }

    /** Artikel referensi (yang pernah dibaca user) */
    public function sourceArticle(): BelongsTo
    {
        return $this->belongsTo(Article::class, 'article_id', 'article_id');
    }

    /** Artikel yang direkomendasikan */
    public function similarArticle(): BelongsTo
    {
        return $this->belongsTo(Article::class, 'similar_article_id', 'article_id');
    }
}
