<?php

namespace App\Livewire\Admin\KnowledgeBase;

use App\Models\KnowledgeBaseArticle;
use App\Models\KnowledgeBaseCategory;
use App\Services\KnowledgeBaseService;
use App\Support\LineDiff;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

#[Layout('layouts.admin')]
class ArticleEditor extends Component
{
    #[Locked]
    public ?int $articleId = null;

    public ?int $category_id = null;

    public string $title = '';

    public string $body = '';

    public string $visibility = KnowledgeBaseArticle::VISIBILITY_INTERNAL;

    public ?int $compareVersionId = null;

    public function mount(?KnowledgeBaseArticle $article = null): void
    {
        Gate::authorize('kb.articles.manage');

        if ($article?->exists) {
            $this->articleId = $article->id;
            $this->fill($article->only(['category_id', 'title', 'body', 'visibility']));
        }
    }

    public function publish(KnowledgeBaseService $knowledgeBase): void
    {
        Gate::authorize('kb.articles.manage');

        $data = $this->validate([
            'category_id' => ['required', 'integer', 'exists:knowledge_base_categories,id'],
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:100000'],
            'visibility' => ['required', Rule::in(KnowledgeBaseArticle::VISIBILITIES)],
        ]);

        $article = $knowledgeBase->publish($this->article(), $data, Auth::user());

        if (! $this->articleId) {
            $this->redirectRoute('admin.kb.articles.edit', $article, navigate: false);
        }
    }

    public function compare(int $versionId): void
    {
        $this->compareVersionId = $versionId;
    }

    public function rollback(int $versionId, KnowledgeBaseService $knowledgeBase): void
    {
        Gate::authorize('kb.articles.manage');

        $article = $this->article();
        abort_unless($article, 404);

        $article = $knowledgeBase->rollback($article, $article->versions()->findOrFail($versionId), Auth::user());

        $this->fill($article->only(['category_id', 'title', 'body', 'visibility']));
        $this->compareVersionId = null;
    }

    public function render()
    {
        $article = $this->article();
        $compared = $this->compareVersionId ? $article?->versions()->find($this->compareVersionId) : null;

        return view('livewire.admin.knowledge-base.article-editor', [
            'article' => $article,
            'categories' => KnowledgeBaseCategory::tree(),
            'versions' => $article?->versions()->with('author')->get() ?? collect(),
            'compared' => $compared,
            'diff' => $compared ? LineDiff::compare($compared->body, $article->body) : [],
        ]);
    }

    private function article(): ?KnowledgeBaseArticle
    {
        return $this->articleId ? KnowledgeBaseArticle::query()->findOrFail($this->articleId) : null;
    }
}
