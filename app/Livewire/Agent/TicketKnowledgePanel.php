<?php

namespace App\Livewire\Agent;

use App\Models\KnowledgeBaseArticle;
use App\Models\Ticket;
use App\Services\KnowledgeBaseService;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Knowledge base search inside the ticket sidebar (8.md): insert an
 * article into the reply (handled by TicketWorkspace via event) or mark
 * the ticket as "gelöst mit Artikel X".
 */
class TicketKnowledgePanel extends Component
{
    #[Locked]
    public int $ticketId;

    public string $kbSearch = '';

    public function insert(int $articleId): void
    {
        Gate::authorize('kb.articles.view');

        $this->dispatch('kb-article-insert', articleId: KnowledgeBaseArticle::query()->findOrFail($articleId)->id);
    }

    public function markResolved(int $articleId): void
    {
        Gate::authorize('kb.articles.view');

        $this->ticket()->update(['resolved_with_article_id' => KnowledgeBaseArticle::query()->findOrFail($articleId)->id]);
    }

    public function clearResolved(): void
    {
        Gate::authorize('kb.articles.view');

        $this->ticket()->update(['resolved_with_article_id' => null]);
    }

    public function render(KnowledgeBaseService $knowledgeBase)
    {
        return view('livewire.agent.ticket-knowledge-panel', [
            'ticket' => $this->ticket()->load('resolvedWithArticle'),
            'results' => $this->kbSearch !== '' ? $knowledgeBase->search($this->kbSearch)->limit(5)->get() : collect(),
        ]);
    }

    private function ticket(): Ticket
    {
        return Ticket::query()->visibleTo(auth()->user())->findOrFail($this->ticketId);
    }
}
