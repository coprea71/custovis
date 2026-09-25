<?php

namespace App\Livewire\Agent;

use App\Models\Team;
use App\Models\Ticket;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Status, priority, assignee and team of a ticket in the workspace sidebar
 * (20.md). Changes are audited by the Auditable trait on Ticket.
 */
class TicketPropertiesPanel extends Component
{
    #[Locked]
    public int $ticketId;

    public string $status = '';

    public string $priority = '';

    public ?int $assigned_to = null;

    public ?int $team_id = null;

    public function mount(): void
    {
        $ticket = $this->ticket();
        $this->fill($ticket->only(['status', 'priority', 'assigned_to', 'team_id']));
    }

    public function save(): void
    {
        $ticket = $this->ticket();
        Gate::authorize('update', $ticket);

        $data = $this->validate([
            'status' => ['required', Rule::in(Ticket::STATUSES)],
            'priority' => ['required', Rule::in(Ticket::PRIORITIES)],
            'team_id' => ['required', 'integer', Rule::in($this->allowedTeams($ticket)->pluck('id'))],
            'assigned_to' => ['nullable', 'integer', Rule::in($this->assignableUserIds((int) $this->team_id))],
        ], ['assigned_to.in' => 'Bearbeiter muss Mitglied des gewählten Teams sein.']);

        $wasClosed = $ticket->status === 'closed';
        $ticket->update([...$data, 'closed_at' => $data['status'] === 'closed' ? ($ticket->closed_at ?? now()) : null]);

        if ($wasClosed && $data['status'] !== 'closed') {
            $ticket->recordReopening(auth()->user()->name, auth()->id());
        }

        $this->dispatch('ticket-updated');
    }

    public function render()
    {
        $ticket = $this->ticket();

        return view('livewire.agent.ticket-properties-panel', [
            'teams' => $this->allowedTeams($ticket),
            'agents' => Team::query()->find($this->team_id)?->users()->where('active', true)->orderBy('name')->get() ?? collect(),
        ]);
    }

    private function ticket(): Ticket
    {
        return Ticket::query()->visibleTo(Auth::user())->findOrFail($this->ticketId);
    }

    /**
     * Own teams (or all with tickets.view.all), always including the current one.
     *
     * @return Collection<int, Team>
     */
    private function allowedTeams(Ticket $ticket): Collection
    {
        $user = Auth::user();
        $teams = $user->can('tickets.view.all') ? Team::query()->get() : $user->teams()->get();

        return $teams->push($ticket->team)->unique('id')->sortBy('name')->values();
    }

    /**
     * @return array<int, int>
     */
    private function assignableUserIds(int $teamId): array
    {
        return Team::query()->find($teamId)?->users()->where('active', true)->pluck('users.id')->all() ?? [];
    }
}
