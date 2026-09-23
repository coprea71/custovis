<?php

namespace Tests\Feature\Itil;

use App\Models\Team;
use App\Models\Ticket;
use App\Models\TicketChange;
use App\Models\User;
use App\Services\ChangeApprovalService;
use App\States\Change\Approved;
use App\States\Change\CabReview;
use App\States\Change\Closed;
use App\States\Change\Draft;
use App\States\Change\Implementing;
use App\States\Change\Rejected;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChangeApprovalServiceTest extends TestCase
{
    use RefreshDatabase;

    private function makeChangeTicket(Team $team): Ticket
    {
        $ticket = Ticket::query()->create([
            'team_id' => $team->id,
            'type' => 'change',
            'source' => 'api',
            'subject' => 'Server-Upgrade',
        ]);

        TicketChange::query()->create([
            'ticket_id' => $ticket->id,
            'state' => Draft::class,
        ]);

        return $ticket->fresh();
    }

    public function test_change_goes_through_draft_cab_review_approved_closed(): void
    {
        $team = Team::query()->create(['name' => 'Ops', 'slug' => 'ops']);
        $approver = User::factory()->create();
        $ticket = $this->makeChangeTicket($team);

        $this->assertInstanceOf(Draft::class, $ticket->change->state);

        $service = new ChangeApprovalService;
        $service->requestApprovals($ticket, collect([$approver->id]));

        $ticket->change->refresh();
        $this->assertInstanceOf(CabReview::class, $ticket->change->state);

        $service->recordDecision($ticket, $approver->id, true);

        $ticket->change->refresh();
        $this->assertInstanceOf(Approved::class, $ticket->change->state);

        $ticket->change->state->transitionTo(Implementing::class);
        $ticket->change->state->transitionTo(Closed::class);

        $this->assertInstanceOf(Closed::class, $ticket->change->refresh()->state);
    }

    public function test_single_rejection_rejects_the_change(): void
    {
        $team = Team::query()->create(['name' => 'Ops', 'slug' => 'ops']);
        $approverA = User::factory()->create();
        $approverB = User::factory()->create();
        $ticket = $this->makeChangeTicket($team);

        $service = new ChangeApprovalService;
        $service->requestApprovals($ticket, collect([$approverA->id, $approverB->id]));

        $service->recordDecision($ticket, $approverA->id, true);
        $service->recordDecision($ticket, $approverB->id, false);

        $this->assertInstanceOf(Rejected::class, $ticket->change->refresh()->state);
    }
}
