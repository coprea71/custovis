<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\KnowledgeBaseArticle;
use App\Models\KnowledgeBaseArticleVersion;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class KnowledgeBaseService
{
    /**
     * Every publish is a new immutable version; the article row mirrors the
     * newest one (for listing/fulltext) and points at it via current_version_id.
     *
     * @param  array{category_id: int, title: string, body: string, visibility: string}  $data
     */
    public function publish(?KnowledgeBaseArticle $article, array $data, User $by): KnowledgeBaseArticle
    {
        return DB::transaction(function () use ($article, $data, $by) {
            $article ??= new KnowledgeBaseArticle;
            $article->fill($data + ['updated_by' => $by->id])->save();

            $version = $article->versions()->create([
                'version_number' => (int) $article->versions()->max('version_number') + 1,
                'title' => $data['title'],
                'body' => $data['body'],
                'visibility' => $data['visibility'],
                'created_by' => $by->id,
            ]);

            $article->update(['current_version_id' => $version->id]);

            AuditLog::record('kb_article.published', $by, null, $article, [
                'version' => $version->version_number,
                'visibility' => $version->visibility,
            ]);

            return $article;
        });
    }

    /**
     * Rolling back republishes the old content as a new version, so history
     * stays append-only and the rollback itself is traceable.
     */
    public function rollback(KnowledgeBaseArticle $article, KnowledgeBaseArticleVersion $version, User $by): KnowledgeBaseArticle
    {
        abort_unless($version->article_id === $article->id, 404);

        return $this->publish($article, [
            'category_id' => $article->category_id,
            'title' => $version->title,
            'body' => $version->body,
            'visibility' => $version->visibility,
        ], $by);
    }

    /**
     * @return Builder<KnowledgeBaseArticle>
     */
    public function search(string $term, bool $publicOnly = false): Builder
    {
        $term = trim($term);

        return KnowledgeBaseArticle::query()
            ->when($publicOnly, fn (Builder $query) => $query->public())
            ->when($term !== '', fn (Builder $query) => $this->matchTerm($query, $term))
            ->when($term === '', fn (Builder $query) => $query->latest('updated_at'));
    }

    /**
     * @param  Builder<KnowledgeBaseArticle>  $query
     */
    private function matchTerm(Builder $query, string $term): void
    {
        if (in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            $query->whereFullText(['title', 'body'], $term);

            return;
        }

        $like = '%'.addcslashes($term, '%_\\').'%';
        $query->where(fn (Builder $inner) => $inner->where('title', 'like', $like)->orWhere('body', 'like', $like));
    }
}
