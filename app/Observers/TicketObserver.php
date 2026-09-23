<?php

namespace App\Observers;

use App\Models\SlaPolicy;
use App\Models\Ticket;

/**
 * Calculates SLA deadlines at ticket creation (5.md). Deliberately plain
 * calendar-time math for the MVP — business_hours exists as a data model
 * for a future business-hours-aware clock, but that refinement isn't
 * required by this session's Aufgaben/Verifikation.
 */
class TicketObserver
{
    public function created(Ticket $ticket): void
    {
        $policy = SlaPolicy::query()
            ->where('team_id', $ticket->team_id)
            ->where('priority', $ticket->priority)
            ->first();

        if (! $policy) {
            return;
        }

        $ticket->updateQuietly([
            'sla_policy_id' => $policy->id,
            'sla_response_due_at' => $ticket->created_at->addMinutes($policy->response_time_minutes),
            'sla_resolution_due_at' => $ticket->created_at->addMinutes($policy->resolution_time_minutes),
        ]);
    }
}
