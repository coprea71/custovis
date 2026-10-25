<?php

namespace App\Livewire\Agent\Concerns;

use App\Models\Ticket;
use App\Services\TicketMergeService;
use Illuminate\Support\Facades\Gate;

/**
 * Selection mode of the ticket list for merging duplicates (TicketWorkspace).
 */
trait MergesTickets
{
    public bool $mergeMode = false;

    /** @var array<int, int|string> ticket ids ticked in the list */
    public array $mergeSelection = [];

    public ?int $mergeTargetId = null;

    public ?string $mergeStatus = null;

    public function toggleMergeMode(): void
    {
        $this->mergeMode = ! $this->mergeMode;
        $this->mergeSelection = [];
        $this->mergeTargetId = null;
        $this->resetErrorBag('merge');
    }

    /**
     * The oldest ticket is the natural default target: it carries the
     * original request and usually the ticket number the customer knows.
     */
    public function updatedMergeSelection(): void
    {
        $ids = $this->mergeIds();

        if (! in_array($this->mergeTargetId, $ids, true)) {
            $this->mergeTargetId = $ids === [] ? null : min($ids);
        }
    }

    public function mergeTickets(TicketMergeService $merger): void
    {
        $ids = $this->mergeIds();

        if (count($ids) < 2 || ! in_array($this->mergeTargetId, $ids, true)) {
            $this->addError('merge', 'Bitte mindestens zwei Tickets und ein Zielticket auswählen.');

            return;
        }

        $tickets = Ticket::query()->visibleTo(auth()->user())->whereIn('id', $ids)->get();
        abort_unless($tickets->count() === count($ids), 404);
        $tickets->each(fn (Ticket $ticket) => Gate::authorize('update', $ticket));

        $target = $merger->merge(
            $tickets->firstWhere('id', $this->mergeTargetId),
            $tickets->where('id', '!=', $this->mergeTargetId)->values(),
            auth()->user(),
        );

        $this->toggleMergeMode();
        $this->mergeStatus = (count($ids) - 1).' Ticket(s) in #'.$target->id.' zusammengeführt.';
        $this->selectTicket($target->id);
    }

    /**
     * @return list<int>
     */
    private function mergeIds(): array
    {
        return array_values(array_unique(array_map('intval', $this->mergeSelection)));
    }
}
