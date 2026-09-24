<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class KnowledgeBaseArticle extends Model
{
    public const VISIBILITY_INTERNAL = 'internal';

    public const VISIBILITY_PUBLIC = 'public';

    public const VISIBILITIES = [self::VISIBILITY_INTERNAL, self::VISIBILITY_PUBLIC];

    protected $fillable = [
        'category_id',
        'title',
        'body',
        'visibility',
        'current_version_id',
        'updated_by',
    ];

    /**
     * @return BelongsTo<KnowledgeBaseCategory, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(KnowledgeBaseCategory::class, 'category_id');
    }

    /**
     * @return HasMany<KnowledgeBaseArticleVersion, $this>
     */
    public function versions(): HasMany
    {
        return $this->hasMany(KnowledgeBaseArticleVersion::class, 'article_id')->orderByDesc('version_number');
    }

    /**
     * @return BelongsTo<KnowledgeBaseArticleVersion, $this>
     */
    public function currentVersion(): BelongsTo
    {
        return $this->belongsTo(KnowledgeBaseArticleVersion::class, 'current_version_id');
    }

    /**
     * @return HasMany<KnowledgeBaseArticleFeedback, $this>
     */
    public function feedback(): HasMany
    {
        return $this->hasMany(KnowledgeBaseArticleFeedback::class, 'article_id');
    }

    /**
     * @param  Builder<KnowledgeBaseArticle>  $query
     */
    public function scopePublic(Builder $query): void
    {
        $query->where('visibility', self::VISIBILITY_PUBLIC);
    }

    /**
     * Markdown rendered with raw HTML escaped and unsafe links dropped —
     * article bodies are user input and are shown in agent and portal UI.
     */
    public function bodyHtml(): string
    {
        return Str::markdown($this->body, ['html_input' => 'escape', 'allow_unsafe_links' => false]);
    }
}
