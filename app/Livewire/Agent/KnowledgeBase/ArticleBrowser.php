<?php

namespace App\Livewire\Agent\KnowledgeBase;

use App\Models\KnowledgeBaseArticle;
use App\Services\KnowledgeBaseService;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

#[Layout('layouts.agent')]
class ArticleBrowser extends Component
{
    #[Locked]
    public ?int $articleId = null;

    public string $search = '';

    public function mount(?KnowledgeBaseArticle $article = null): void
    {
        Gate::authorize('kb.articles.view');

        $this->articleId = $article?->id;
    }

    public function render(KnowledgeBaseService $knowledgeBase)
    {
        return view('livewire.agent.knowledge-base.article-browser', [
            'articles' => $knowledgeBase->search($this->search)->with('category')->limit(50)->get(),
            'article' => $this->articleId ? KnowledgeBaseArticle::query()->with('category')->findOrFail($this->articleId) : null,
        ]);
    }
}
