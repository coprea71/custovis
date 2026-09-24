<?php

namespace App\Policies;

use App\Models\Team;
use App\Models\User;

class TeamPolicy
{
    /**
     * Team members see their own team; management may look into any team.
     */
    public function viewDashboard(User $user, Team $team): bool
    {
        return $user->teams()->whereKey($team->id)->exists()
            || $user->can('dashboard.management.view');
    }
}
