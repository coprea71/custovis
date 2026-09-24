<?php

namespace App\Livewire\Agent;

use App\Jobs\SendTicketReplyJob;
use App\Jobs\SendWhatsappReplyJob;
use App\Models\CannedResponse;
use App\Models\KnowledgeBaseArticle;
use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Models\WhatsappTemplate;
use App\Services\WhatsappMessageSender;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.agent')]
class TicketWorkspace extends Component
{
    use WithPagination;

    public ?int $ticketId = null;

    public string $statusFilter = 'all';

    public string $search = '';

    public string $replyVisibility = TicketMessage::VISIBILITY_PUBLIC;

    public string $replyBody = '';

    public string $newTag = '';

    public function mount(?Ticket $ticket = null): void
    {
        if ($ticket?->exists) {
            Gate::authorize('view', $ticket);
        }

        $this->ticketId = $ticket?->id;
    }

    public function selectTicket(int $ticketId): void
    {
        $this->ticketId = $ticketId;
        $this->replyBody = '';
        $this->replyVisibility = TicketMessage::VISIBILITY_PUBLIC;
    }

    #[On('ticket-updated')]
    public function refreshTicket(): void
    {
        // re-render only; the properties panel already saved the change
    }

    public function setStatusFilter(string $status): void
    {
        abort_unless(in_array($status, ['all', 'mine', ...Ticket::STATUSES], true), 422);

        $this->statusFilter = $status;
        $this->resetPage();
    }

    public function sendReply(): void
    {
        $this->validate([
            'replyBody' => ['required', 'string', 'min:1'],
            'replyVisibility' => ['required', 'in:'.TicketMessage::VISIBILITY_PUBLIC.','.TicketMessage::VISIBILITY_INTERNAL_NOTE],
        ]);

        $ticket = $this->selectedTicket();

        abort_unless($ticket, 404);

        $message = $ticket->messages()->create([
            'visibility' => $this->replyVisibility,
            'direction' => 'outgoing',
            'author_user_id' => auth()->id(),
            'body_html' => nl2br(e($this->replyBody)),
            'body_text' => $this->replyBody,
            'message_id' => 'custovis-'.uniqid('', true).'@'.parse_url(config('app.url'), PHP_URL_HOST),
        ]);

        if ($message->visibility === TicketMessage::VISIBILITY_PUBLIC) {
            if ($ticket->source === 'mailbox') {
                SendTicketReplyJob::dispatch($message);
            } elseif ($ticket->source === 'whatsapp') {
                SendWhatsappReplyJob::dispatch($message);
            }
        }

        $this->replyBody = '';
    }

    public function sendWhatsappTemplate(int $templateId): void
    {
        $ticket = $this->selectedTicket();
        abort_unless($ticket && $ticket->source === 'whatsapp', 404);

        $template = WhatsappTemplate::query()->where('whatsapp_account_id', $ticket->whatsapp_account_id)->findOrFail($templateId);

        $message = $ticket->messages()->create([
            'visibility' => TicketMessage::VISIBILITY_PUBLIC,
            'direction' => 'outgoing',
            'author_user_id' => auth()->id(),
            'body_text' => $template->approved_body,
            'message_id' => 'custovis-'.uniqid('', true).'@'.parse_url(config('app.url'), PHP_URL_HOST),
            'whatsapp_message_type' => 'template',
        ]);

        app(WhatsappMessageSender::class)->sendTemplate($message, $template);
    }

    public function whatsappSessionWindowOpen(): bool
    {
        $ticket = $this->selectedTicket();

        if (! $ticket || $ticket->source !== 'whatsapp') {
            return true;
        }

        return app(WhatsappMessageSender::class)->isWithinSessionWindow($ticket);
    }

    public function insertCannedResponse(int $cannedResponseId): void
    {
        $response = CannedResponse::query()->findOrFail($cannedResponseId);
        $this->replyBody = trim($this->replyBody."\n".$response->body);
    }

    #[On('kb-article-insert')]
    public function insertKnowledgeArticle(int $articleId): void
    {
        Gate::authorize('kb.articles.view');

        $article = KnowledgeBaseArticle::query()->findOrFail($articleId);
        $this->replyBody = trim($this->replyBody."\n\n".$article->title."\n\n".$article->body);
    }

    public function addTag(): void
    {
        $tag = trim($this->newTag);
        $this->newTag = '';

        if ($tag === '') {
            return;
        }

        $ticket = $this->selectedTicket();
        abort_unless($ticket, 404);

        $tags = $ticket->tags ?? [];
        if (! in_array($tag, $tags, true)) {
            $tags[] = $tag;
            $ticket->update(['tags' => $tags]);
        }
    }

    public function removeTag(string $tag): void
    {
        $ticket = $this->selectedTicket();
        abort_unless($ticket, 404);

        $ticket->update(['tags' => array_values(array_diff($ticket->tags ?? [], [$tag]))]);
    }

    public function selectedTicket(): ?Ticket
    {
        if (! $this->ticketId) {
            return null;
        }

        // Scoped so a tampered ticketId can never reach another team's ticket.
        return Ticket::query()->visibleTo(auth()->user())->with('messages.attachments', 'assignee', 'customer')->find($this->ticketId);
    }

    public function render()
    {
        $tickets = Ticket::query()
            ->visibleTo(auth()->user())
            ->when($this->statusFilter === 'mine', fn ($query) => $query->where('assigned_to', auth()->id())->where('status', '!=', 'closed'))
            ->when(in_array($this->statusFilter, Ticket::STATUSES, true), fn ($query) => $query->where('status', $this->statusFilter))
            ->when($this->search !== '', fn ($query) => $query->where(
                fn ($q) => $q->where('subject', 'like', "%{$this->search}%")
                    ->orWhere('requester_email', 'like', "%{$this->search}%")
            ))
            ->latest()
            ->paginate(20);

        $ticket = $this->selectedTicket();

        return view('livewire.agent.ticket-workspace', [
            'tickets' => $tickets,
            'ticket' => $ticket,
            'cannedResponses' => $ticket
                ? CannedResponse::query()->where('team_id', $ticket->team_id)->orderBy('title')->get()
                : collect(),
            'whatsappSessionOpen' => $this->whatsappSessionWindowOpen(),
            'whatsappTemplates' => $ticket && $ticket->source === 'whatsapp'
                ? WhatsappTemplate::query()->where('whatsapp_account_id', $ticket->whatsapp_account_id)->orderBy('name')->get()
                : collect(),
        ]);
    }
}
