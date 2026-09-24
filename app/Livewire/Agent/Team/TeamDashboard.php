<?php

namespace App\Livewire\Agent\Team;

use App\Models\Team;
use App\Services\Dashboard\DashboardSnapshotService;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

#[Layout('layouts.agent')]
class TeamDashboard extends Component
{
    #[Locked]
    public Team $team;

    public function mount(Team $team): void
    {
        Gate::authorize('viewDashboard', $team);

        $this->team = $team;
    }

    public function render(DashboardSnapshotService $snapshots)
    {
        return view('livewire.agent.team.team-dashboard', [
            'snapshot' => $snapshots->forScope($this->team),
            // Live, not snapshotted: cheap (indexed, limited) and only useful when current.
            'recentTickets' => $this->team->tickets()->with('assignee')->latest('updated_at')->limit(10)->get(),
        ]);
    }
}
