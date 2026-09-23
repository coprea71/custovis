<?php

namespace App\Core\Ai\Support;

use App\Models\AiBudget;
use App\Models\AiUsageLog;
use App\Models\Team;

/**
 * Enforces a team's monthly AI spend limit (6.md). Teams without a
 * configured budget are unlimited — the budget is opt-in, not a default
 * restriction.
 */
class AiBudgetService
{
    public function withinBudget(Team $team): bool
    {
        $budget = AiBudget::query()->where('team_id', $team->id)->first();

        if (! $budget) {
            return true;
        }

        return $this->spentCentsThisMonth($team) < $budget->monthly_limit_cents;
    }

    public function spentCentsThisMonth(Team $team): int
    {
        return (int) AiUsageLog::query()
            ->where('team_id', $team->id)
            ->whereYear('created_at', now()->year)
            ->whereMonth('created_at', now()->month)
            ->sum('cost_cents');
    }
}
