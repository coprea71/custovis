<?php

namespace App\Services;

use App\Models\Team;
use App\Models\Ticket;
use App\Models\TicketChange;
use App\Models\TicketIncident;
use App\Models\TicketMessage;
use App\Models\TicketProblem;
use App\Models\User;
use App\States\Change\Draft;
use App\States\Incident\New_ as NewIncident;
use App\States\Problem\New_ as NewProblem;
use Illuminate\Support\Facades\DB;

/**
 * Tickets created by an agent in the workspace (e.g. from a phone call or
 * walk-in, 20.md). ITIL types get their extension row in the start state.
 */
class ManualTicketService
{
    public const TYPES = ['support_ticket', 'incident', 'problem', 'change'];

    /**
     * @param  array{team_id: int, type: string, subject: string, priority: string, requester_name?: string|null, requester_email?: string|null, requester_phone?: string|null, body: string}  $data
     */
    public function create(array $data, User $agent): Ticket
    {
        return DB::transaction(function () use ($data, $agent) {
            $ticket = Team::query()->findOrFail($data['team_id'])->tickets()->create([
                'type' => $data['type'],
                'source' => 'manual',
                'subject' => $data['subject'],
                'priority' => $data['priority'],
                'requester_name' => $data['requester_name'] ?? null,
                'requester_email' => $data['requester_email'] ?? null,
                'requester_phone' => $data['requester_phone'] ?? null,
                'assigned_to' => $agent->id,
            ]);

            $ticket->messages()->create([
                'visibility' => TicketMessage::VISIBILITY_PUBLIC,
                'direction' => 'incoming',
                'external_author_name' => $data['requester_name'] ?? null,
                'external_author_email' => $data['requester_email'] ?? null,
                'body_text' => $data['body'],
                'body_html' => nl2br(e($data['body'])),
            ]);

            $this->createItilExtension($ticket);

            return $ticket;
        });
    }

    private function createItilExtension(Ticket $ticket): void
    {
        match ($ticket->type) {
            'incident' => TicketIncident::query()->create(['ticket_id' => $ticket->id, 'state' => NewIncident::class]),
            'problem' => TicketProblem::query()->create(['ticket_id' => $ticket->id, 'state' => NewProblem::class]),
            'change' => TicketChange::query()->create(['ticket_id' => $ticket->id, 'state' => Draft::class]),
            default => null,
        };
    }
}
