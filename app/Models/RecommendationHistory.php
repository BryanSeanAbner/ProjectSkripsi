<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecommendationHistory extends Model
{
    protected $table      = 'recommendation_history';
    protected $primaryKey = 'recommendation_id';
    public    $timestamps = false;

    protected $fillable = [
        'user_id', 'article_id', 'rank_position', 'generated_at',
    ];

    protected function casts(): array
    {
        return [
            'generated_at'  => 'datetime',
            'rank_position' => 'integer',
        ];
    }

    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class, 'article_id', 'article_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }
}
