<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KnowledgeBaseArticleFeedback extends Model
{
    protected $table = 'knowledge_base_article_feedback';

    protected $fillable = [
        'article_id',
        'helpful',
    ];

    protected function casts(): array
    {
        return [
            'helpful' => 'boolean',
        ];
    }
}
