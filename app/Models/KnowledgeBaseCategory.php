<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class KnowledgeBaseCategory extends Model
{
    protected $fillable = [
        'parent_id',
        'name',
        'position',
    ];

    /**
     * @return BelongsTo<KnowledgeBaseCategory, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * @return HasMany<KnowledgeBaseCategory, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('position')->orderBy('name');
    }

    /**
     * @return HasMany<KnowledgeBaseArticle, $this>
     */
    public function articles(): HasMany
    {
        return $this->hasMany(KnowledgeBaseArticle::class, 'category_id');
    }

    /**
     * Flattened tree (depth-first) with a `depth` attribute for indented
     * select boxes — one query, built in memory.
     *
     * @return Collection<int, KnowledgeBaseCategory>
     */
    public static function tree(): Collection
    {
        $byParent = self::query()->orderBy('position')->orderBy('name')->get()->groupBy('parent_id');
        $flatten = function ($parentId, int $depth) use (&$flatten, $byParent): array {
            return collect($byParent->get($parentId ?? '', []))
                ->flatMap(fn (self $category) => [$category->setAttribute('depth', $depth), ...$flatten($category->id, $depth + 1)])
                ->all();
        };

        return collect($flatten(null, 0));
    }

    /**
     * @param  array<int, int>  $ids
     * @return array<int, int> the given ids plus all of their descendants
     */
    public static function withDescendantIds(array $ids): array
    {
        $childrenByParent = self::query()->whereNotNull('parent_id')->get(['id', 'parent_id'])->groupBy('parent_id');
        $result = [];
        $queue = array_map('intval', $ids);

        while ($queue !== []) {
            $id = array_shift($queue);

            if (! in_array($id, $result, true)) {
                $result[] = $id;
                $queue = [...$queue, ...$childrenByParent->get($id, collect())->pluck('id')->all()];
            }
        }

        return $result;
    }
}
