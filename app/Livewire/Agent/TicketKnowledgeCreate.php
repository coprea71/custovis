<?php

namespace App\Livewire\Agent;

use App\Models\KnowledgeBaseArticle;
use App\Models\KnowledgeBaseCategory;
use App\Models\Ticket;
use App\Services\KnowledgeBaseService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Lets agents add a knowledge base article from the ticket sidebar in a
 * modal, so solutions are captured while the case is still fresh.
 */
class TicketKnowledgeCreate extends Component
{
    #[Locked]
    public int $ticketId;

    public bool $open = false;

    public ?int $category_id = null;

    public string $title = '';

    public string $body = '';

    public string $visibility = KnowledgeBaseArticle::VISIBILITY_INTERNAL;

    public ?string $status = null;

    public function mount(): void
    {
        Gate::authorize('kb.articles.create');
    }

    public function openModal(): void
    {
        Gate::authorize('kb.articles.create');

        $this->reset(['category_id', 'body', 'visibility', 'status']);
        $this->title = (string) $this->ticket()->subject;
        $this->resetValidation();
        $this->open = true;
    }

    public function closeModal(): void
    {
        $this->open = false;
    }

    public function save(KnowledgeBaseService $knowledgeBase): void
    {
        Gate::authorize('kb.articles.create');
        $this->ticket();

        $data = $this->validate([
            'category_id' => ['required', 'integer', 'exists:knowledge_base_categories,id'],
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:100000'],
            'visibility' => ['required', Rule::in(KnowledgeBaseArticle::VISIBILITIES)],
        ]);

        // Customer-facing (public) articles stay with knowledge base maintainers.
        if (! Gate::allows('kb.articles.manage')) {
            $data['visibility'] = KnowledgeBaseArticle::VISIBILITY_INTERNAL;
        }

        $knowledgeBase->publish(null, $data, Auth::user());

        $this->open = false;
        $this->status = 'Artikel angelegt.';
    }

    public function render()
    {
        return view('livewire.agent.ticket-knowledge-create', [
            'categories' => $this->open ? KnowledgeBaseCategory::tree() : collect(),
        ]);
    }

    private function ticket(): Ticket
    {
        return Ticket::query()->visibleTo(Auth::user())->findOrFail($this->ticketId);
    }
}
