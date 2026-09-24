<?php

namespace App\Livewire\Portal;

use App\Models\KnowledgeBaseArticle;
use App\Services\KnowledgeBaseService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

#[Layout('layouts.portal')]
class KnowledgeBase extends Component
{
    #[Locked]
    public ?int $articleId = null;

    public string $search = '';

    public function mount(?int $article = null): void
    {
        // Internal articles must be indistinguishable from missing ones.
        $this->articleId = $article ? KnowledgeBaseArticle::query()->public()->findOrFail($article)->id : null;
    }

    public function render(KnowledgeBaseService $knowledgeBase)
    {
        return view('livewire.portal.knowledge-base', [
            'articles' => $knowledgeBase->search($this->search, publicOnly: true)->limit(30)->get(),
            'article' => $this->articleId ? KnowledgeBaseArticle::query()->public()->find($this->articleId) : null,
        ]);
    }
}
