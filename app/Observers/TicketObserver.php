<?php

namespace App\Observers;

use App\Models\SlaPolicy;
use App\Models\Ticket;
use App\Services\Itil\BusinessHoursCalendar;

/**
 * Calculates SLA deadlines at ticket creation (5.md). Deadlines run in the
 * team's business hours when those are maintained (21.md), otherwise in
 * calendar time.
 */
class TicketObserver
{
    public function __construct(private readonly BusinessHoursCalendar $calendar) {}

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
            'sla_response_due_at' => $this->calendar->addMinutes($ticket->team_id, $ticket->created_at, $policy->response_time_minutes),
            'sla_resolution_due_at' => $this->calendar->addMinutes($ticket->team_id, $ticket->created_at, $policy->resolution_time_minutes),
        ]);
    }
}
