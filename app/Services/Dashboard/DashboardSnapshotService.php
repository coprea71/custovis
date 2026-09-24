<?php

namespace App\Services\Dashboard;

use App\Models\DashboardSnapshot;
use App\Models\Team;

class DashboardSnapshotService
{
    public function __construct(private readonly DashboardMetrics $metrics) {}

    /**
     * Reads the cached aggregation; only computes live when no snapshot
     * exists yet (fresh install / new team before the next hourly run).
     */
    public function forScope(?Team $team): DashboardSnapshot
    {
        return DashboardSnapshot::query()->where('team_id', $team?->id)->first()
            ?? $this->refresh($team);
    }

    public function refresh(?Team $team): DashboardSnapshot
    {
        return DashboardSnapshot::query()->updateOrCreate(
            ['team_id' => $team?->id],
            ['data' => $this->metrics->compute($team), 'generated_at' => now()]
        );
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
