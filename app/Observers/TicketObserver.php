<?php

namespace App\Observers;

use App\Models\SlaPolicy;
use App\Models\Ticket;
use App\Models\User;
use App\Notifications\NewTicketNotification;
use App\Services\Itil\BusinessHoursCalendar;
use Illuminate\Support\Facades\Notification;

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
        $this->applySlaPolicy($ticket);
        $this->notifyTeamMembers($ticket);
    }

    private function applySlaPolicy(Ticket $ticket): void
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

    /**
     * Only active members who opted in; the agent creating the ticket
     * already knows about it.
     */
    private function notifyTeamMembers(Ticket $ticket): void
    {
        $recipients = User::query()
            ->where('active', true)
            ->where('notify_new_tickets', true)
            ->whereHas('teams', fn ($query) => $query->whereKey($ticket->team_id))
            ->when(auth('web')->id(), fn ($query, $id) => $query->whereKeyNot($id))
            ->get();

        Notification::send($recipients, new NewTicketNotification($ticket));
    }
}
