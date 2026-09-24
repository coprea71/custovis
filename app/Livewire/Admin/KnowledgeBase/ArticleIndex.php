<?php

namespace App\Livewire\Admin\KnowledgeBase;

use App\Services\KnowledgeBaseService;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.admin')]
class ArticleIndex extends Component
{
    use WithPagination;

    public string $search = '';

    public function mount(): void
    {
        Gate::authorize('kb.articles.manage');
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function render(KnowledgeBaseService $knowledgeBase)
    {
        return view('livewire.admin.knowledge-base.article-index', [
            'articles' => $knowledgeBase->search($this->search)->with('category')->paginate(25),
        ]);
    }
}
