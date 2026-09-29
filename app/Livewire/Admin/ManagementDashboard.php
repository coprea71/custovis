<?php

namespace App\Livewire\Admin;

use App\Models\Mailbox;
use App\Models\Team;
use App\Services\Dashboard\DashboardSnapshotService;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.admin')]
class ManagementDashboard extends Component
{
    public function mount(): void
    {
        Gate::authorize('dashboard.management.view');
    }

    public function render(DashboardSnapshotService $snapshots)
    {
        $canSeeAllTickets = Gate::allows('tickets.view.all');

        return view('livewire.admin.management-dashboard', [
            'snapshot' => $snapshots->forScope(null),
            'failingMailboxCount' => Gate::allows('mailboxes.manage') ? Mailbox::query()->withFetchError()->count() : 0,
            'openTicketsUrl' => $canSeeAllTickets ? route('agent.tickets.index', ['status' => 'unresolved']) : null,
            'teamTicketLinks' => $canSeeAllTickets ? $this->teamTicketLinks() : [],
        ]);
    }

    /**
     * The snapshot keys teams by name, so the ids are looked up live
     * instead of changing the stored snapshot format.
     *
     * @return array<string, string> team name => filtered ticket list url
     */
    private function teamTicketLinks(): array
    {
        return Team::query()->pluck('id', 'name')
            ->map(fn (int $id) => route('agent.tickets.index', ['team' => $id, 'status' => 'unresolved']))
            ->all();
    }
}
