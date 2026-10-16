<?php

namespace App\Services\Dashboard;

use App\Models\DashboardSnapshot;
use App\Models\Team;
use App\Models\Ticket;

class DashboardSnapshotService
{
    public function __construct(private readonly DashboardMetrics $metrics) {}

    /**
     * Reads the cached aggregation; recomputes only when there is no snapshot
     * yet or a ticket in scope changed since — otherwise a ticket closed after
     * the hourly run would keep counting as open until the next one.
     */
    public function forScope(?Team $team): DashboardSnapshot
    {
        $snapshot = DashboardSnapshot::query()->where('team_id', $team?->id)->first();

        return $snapshot && ! $this->ticketsChangedSince($team, $snapshot)
            ? $snapshot
            : $this->refresh($team);
    }

    public function refresh(?Team $team): DashboardSnapshot
    {
        return DashboardSnapshot::query()->updateOrCreate(
            ['team_id' => $team?->id],
            ['data' => $this->metrics->compute($team), 'generated_at' => now()]
        );
    }

    private function ticketsChangedSince(?Team $team, DashboardSnapshot $snapshot): bool
    {
        return Ticket::query()
            ->when($team, fn ($query) => $query->where('team_id', $team->id))
            ->where('updated_at', '>=', $snapshot->generated_at)
            ->exists();
    }

    public function refreshAll(): int
    {
        $this->refresh(null);
        $count = 1;

        Team::query()->each(function (Team $team) use (&$count) {
            $this->refresh($team);
            $count++;
        });

        return $count;
    }
}
