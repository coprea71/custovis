<?php

namespace App\Livewire\Agent;

use App\Jobs\SendTicketReplyJob;
use App\Models\CannedResponse;
use App\Models\Ticket;
use App\Models\TicketMessage;
use Livewire\Attributes\Layout;
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
        $this->ticketId = $ticket?->id;
    }

    public function selectTicket(int $ticketId): void
    {
        $this->ticketId = $ticketId;
        $this->replyBody = '';
        $this->replyVisibility = TicketMessage::VISIBILITY_PUBLIC;
    }

    public function setStatusFilter(string $status): void
    {
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

        if ($message->visibility === TicketMessage::VISIBILITY_PUBLIC && $ticket->source === 'mailbox') {
            SendTicketReplyJob::dispatch($message);
        }

        $this->replyBody = '';
    }

    public function insertCannedResponse(int $cannedResponseId): void
    {
        $response = CannedResponse::query()->findOrFail($cannedResponseId);
        $this->replyBody = trim($this->replyBody."\n".$response->body);
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

        return Ticket::query()->with('messages.attachments', 'assignee', 'customer')->find($this->ticketId);
    }

    public function render()
    {
        $tickets = Ticket::query()
            ->when($this->statusFilter !== 'all', fn ($query) => $query->where('status', $this->statusFilter))
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
        ]);
    }
}
