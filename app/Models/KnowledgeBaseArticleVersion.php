<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KnowledgeBaseArticleVersion extends Model
{
    protected $fillable = [
        'article_id',
        'version_number',
        'title',
        'body',
        'visibility',
        'created_by',
    ];

    /**
     * @return BelongsTo<KnowledgeBaseArticle, $this>
     */
    public function article(): BelongsTo
    {
        return $this->belongsTo(KnowledgeBaseArticle::class, 'article_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
