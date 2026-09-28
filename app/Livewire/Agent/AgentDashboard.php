<?php

namespace App\Livewire\Agent;

use App\Models\Team;
use App\Services\Dashboard\DashboardSnapshotService;
use App\Services\ModuleAccess;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Start page of the agent area: the dashboards of all teams the user is a
 * member of, stacked. Membership is the authorization (same rule as
 * TeamPolicy::viewDashboard), so foreign teams can never appear here.
 */
#[Layout('layouts.agent')]
class AgentDashboard extends Component
{
    public function mount(ModuleAccess $modules)
    {
        $user = auth()->user();

        // Without dashboards the start page would be empty — the ticket list is the useful fallback.
        if (! $modules->allows($user, 'reporting') || ! $user->teams()->exists()) {
            return redirect()->route('agent.tickets.index');
        }
    }

    public function render(DashboardSnapshotService $snapshots)
    {
        $sections = auth()->user()->teams()->orderBy('name')->get()
            ->map(fn (Team $team) => [
                'team' => $team,
                'snapshot' => $snapshots->forScope($team),
                'recentTickets' => $team->recentlyUpdatedTickets(),
            ]);

        return view('livewire.agent.agent-dashboard', ['sections' => $sections]);
    }
}
