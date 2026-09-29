<?php

namespace App\Livewire\Agent;

use App\Jobs\SendTicketReplyJob;
use App\Jobs\SendWhatsappReplyJob;
use App\Models\CannedResponse;
use App\Models\KnowledgeBaseArticle;
use App\Models\SpamRule;
use App\Models\Team;
use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Models\TicketReadState;
use App\Models\WhatsappTemplate;
use App\Services\SpamFilterService;
use App\Services\WhatsappMessageSender;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Attributes\Session;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.agent')]
class TicketWorkspace extends Component
{
    use WithPagination;

    public const SORT_FIELDS = ['id', 'created_at', 'priority'];

    /** "unresolved" = everything but closed, matching the dashboards' "Offene Tickets" count. */
    public const LIST_FILTERS = ['all', 'mine', 'unresolved', ...Ticket::STATUSES];

    public ?int $ticketId = null;

    public string $statusFilter = 'open';

    /** Set by dashboard links (?team=ID); locked so it is only ever validated in mount. */
    #[Locked]
    #[Url(as: 'team', except: null)]
    public ?int $teamFilter = null;

    public string $sortField = 'priority';

    public string $sortDirection = 'desc';

    public string $search = '';

    public bool $unreadOnly = false;

    /** Read position when the ticket was opened; messages above it are shown as new. */
    public int $newSinceMessageId = 0;

    #[Session]
    public bool $newestMessagesFirst = true;

    public string $replyVisibility = TicketMessage::VISIBILITY_PUBLIC;

    public string $replyBody = '';

    public string $newTag = '';

    public function mount(?Ticket $ticket = null): void
    {
        if ($ticket?->exists) {
            Gate::authorize('view', $ticket);
        }

        $this->ticketId = $ticket?->id;
        $this->restoreFilters();
        $this->applyLinkFilters();
        $this->rememberReadPosition();
    }

    /**
     * Dashboard tiles link here with ?team=ID&status=open. Both values are
     * whitelisted; a team the agent cannot see is silently dropped.
     */
    private function applyLinkFilters(): void
    {
        $status = request()->query('status');
        if (in_array($status, self::LIST_FILTERS, true)) {
            $this->statusFilter = $status;
        }

        if ($this->teamFilter !== null && ! $this->visibleTeams()->whereKey($this->teamFilter)->exists()) {
            $this->teamFilter = null;
        }
    }

    /**
     * @return Builder<Team>
     */
    private function visibleTeams(): Builder
    {
        $user = auth()->user();

        return Team::query()->when(
            ! $user->can('tickets.view.all'),
            fn (Builder $query) => $query->whereIn('id', $user->teams()->select('teams.id'))
        );
    }

    public function clearTeamFilter(): void
    {
        $this->teamFilter = null;
        $this->resetPage();
    }

    /**
     * Stored values are re-checked against the whitelists, because the
     * allowed statuses or sort fields may change between releases.
     */
    private function restoreFilters(): void
    {
        $filters = auth()->user()->ticket_filters ?? [];

        if (in_array($filters['status'] ?? null, self::LIST_FILTERS, true)) {
            $this->statusFilter = $filters['status'];
        }
        if (in_array($filters['sort_field'] ?? null, self::SORT_FIELDS, true)) {
            $this->sortField = $filters['sort_field'];
        }
        if (in_array($filters['sort_direction'] ?? null, ['asc', 'desc'], true)) {
            $this->sortDirection = $filters['sort_direction'];
        }
        $this->unreadOnly = (bool) ($filters['unread_only'] ?? false);
    }

    private function saveFilters(): void
    {
        auth()->user()->forceFill(['ticket_filters' => [
            'status' => $this->statusFilter,
            'sort_field' => $this->sortField,
            'sort_direction' => $this->sortDirection,
            'unread_only' => $this->unreadOnly,
        ]])->save();
    }

    public function selectTicket(int $ticketId): void
    {
        $this->ticketId = $ticketId;
        $this->replyBody = '';
        $this->replyVisibility = TicketMessage::VISIBILITY_PUBLIC;
        $this->rememberReadPosition();
    }

    private function rememberReadPosition(): void
    {
        $this->newSinceMessageId = $this->ticketId ? TicketReadState::watermarkFor(auth()->user(), $this->ticketId) : 0;
    }

    public function toggleUnreadOnly(): void
    {
        $this->unreadOnly = ! $this->unreadOnly;
        $this->saveFilters();
        $this->resetPage();
        $this->deselectTicketHiddenByFilter();
    }

    /**
     * Closes the ticket as well, otherwise the next render would mark it
     * read again right away.
     */
    public function markUnread(): void
    {
        $ticket = $this->selectedTicket();
        abort_unless($ticket, 404);

        TicketReadState::markUnread(auth()->user(), $ticket);
        $this->ticketId = null;
        $this->replyBody = '';
    }

    /**
     * Blocks the sender (or their domain) for the ticket's team and moves the
     * ticket to the admin spam folder.
     */
    public function markSpam(string $type): void
    {
        abort_unless(in_array($type, SpamRule::TYPES, true), 422);

        $ticket = $this->selectedTicket();
        abort_unless($ticket && $ticket->source === 'mailbox', 404);

        app(SpamFilterService::class)->markAsSpam($ticket, $type, auth()->user());
        $this->ticketId = null;
        $this->replyBody = '';
    }

    /**
     * Only the tickets of the current view, so a filtered list can be cleared
     * without touching tickets the agent has not looked at.
     */
    public function markAllRead(): void
    {
        $this->filteredTickets()
            ->unreadFor(auth()->user())
            ->withMax('messages', 'id')
            ->get()
            ->each(fn (Ticket $ticket) => TicketReadState::moveWatermark(auth()->user(), $ticket->id, (int) $ticket->messages_max_id));
    }

    #[On('ticket-updated')]
    public function refreshTicket(): void
    {
        // re-render only; the properties panel already saved the change
    }

    public function setStatusFilter(string $status): void
    {
        abort_unless(in_array($status, self::LIST_FILTERS, true), 422);

        $this->statusFilter = $status;
        $this->saveFilters();
        $this->resetPage();
        $this->deselectTicketHiddenByFilter();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
        $this->deselectTicketHiddenByFilter();
    }

    /**
     * A ticket that is no longer in the filtered list must not stay open on
     * the right, otherwise agents type into a ticket they can no longer see.
     */
    private function deselectTicketHiddenByFilter(): void
    {
        if ($this->ticketId && ! $this->filteredTickets()->whereKey($this->ticketId)->exists()) {
            $this->ticketId = null;
            $this->replyBody = '';
        }
    }

    public function sortTickets(string $field): void
    {
        abort_unless(in_array($field, self::SORT_FIELDS, true), 422);

        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = $field === 'priority' ? 'desc' : 'asc';
        }

        $this->saveFilters();
        $this->resetPage();
    }

    public function toggleMessageOrder(): void
    {
        $this->newestMessagesFirst = ! $this->newestMessagesFirst;
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

    /**
     * Ticket id is always the final tiebreaker so the oldest tickets come first
     * within equal values. Public Livewire properties can be tampered with,
     * hence the whitelist fallback here and not only in sortTickets().
     *
     * @param  Builder<Ticket>  $query
     */
    private function applySort(Builder $query): void
    {
        $direction = $this->sortDirection === 'asc' ? 'asc' : 'desc';

        match (in_array($this->sortField, self::SORT_FIELDS, true) ? $this->sortField : 'priority') {
            'id' => $query->orderBy('id', $direction),
            'created_at' => $query->orderBy('created_at', $direction)->orderBy('id'),
            'priority' => $query->orderByPriority($direction)->orderBy('id'),
        };
    }

    /**
     * @return Builder<Ticket>
     */
    private function filteredTickets(): Builder
    {
        return Ticket::query()
            ->visibleTo(auth()->user())
            ->when($this->teamFilter !== null, fn ($query) => $query->where('team_id', $this->teamFilter))
            ->when($this->statusFilter === 'mine', fn ($query) => $query->where('assigned_to', auth()->id())->where('status', '!=', 'closed'))
            ->when($this->statusFilter === 'unresolved', fn ($query) => $query->where('status', '!=', 'closed'))
            ->when(in_array($this->statusFilter, Ticket::STATUSES, true), fn ($query) => $query->where('status', $this->statusFilter))
            ->when($this->unreadOnly, fn ($query) => $query->unreadFor(auth()->user()))
            ->when($this->search !== '', fn ($query) => $query->where(
                fn ($q) => $q->where('subject', 'like', "%{$this->search}%")
                    ->orWhere('requester_email', 'like', "%{$this->search}%")
            ));
    }

    public function render()
    {
        $ticket = $this->selectedTicket();

        // Before the list query, so the open ticket is not flagged as unread.
        if ($ticket) {
            TicketReadState::markRead(auth()->user(), $ticket);
        }

        $tickets = $this->filteredTickets()
            ->withUnreadFlag(auth()->user())
            ->tap(fn ($query) => $this->applySort($query))
            ->paginate(20);

        return view('livewire.agent.ticket-workspace', [
            'tickets' => $tickets,
            'ticket' => $ticket,
            'filterTeam' => $this->teamFilter !== null ? Team::query()->find($this->teamFilter) : null,
            'unreadCount' => $this->filteredTickets()->unreadFor(auth()->user())->count(),
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
