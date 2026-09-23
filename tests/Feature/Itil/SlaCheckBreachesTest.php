<?php

namespace Tests\Feature\Itil;

use App\Events\SlaBreached;
use App\Models\SlaPolicy;
use App\Models\Team;
use App\Models\Ticket;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class SlaCheckBreachesTest extends TestCase
{
    use RefreshDatabase;

    public function test_ticket_created_with_matching_policy_gets_sla_deadlines(): void
    {
        $team = Team::query()->create(['name' => 'Support', 'slug' => 'support']);
        SlaPolicy::query()->create([
            'team_id' => $team->id,
            'name' => 'Standard',
            'priority' => 'normal',
            'response_time_minutes' => 60,
            'resolution_time_minutes' => 480,
        ]);

        $ticket = Ticket::query()->create([
            'team_id' => $team->id,
            'type' => 'support_ticket',
            'source' => 'api',
            'subject' => 'Test',
            'priority' => 'normal',
        ]);

        $ticket->refresh();
        $this->assertNotNull($ticket->sla_response_due_at);
        $this->assertNotNull($ticket->sla_resolution_due_at);
    }

    public function test_overdue_ticket_is_marked_breached_and_fires_event(): void
    {
        Event::fake();

        $team = Team::query()->create(['name' => 'Support', 'slug' => 'support']);
        $ticket = Ticket::query()->create([
            'team_id' => $team->id,
            'type' => 'support_ticket',
            'source' => 'api',
            'subject' => 'Overdue',
        ]);
        $ticket->updateQuietly(['sla_resolution_due_at' => now()->subHour()]);

        $this->artisan('sla:check-breaches')->assertSuccessful();

        $ticket->refresh();
        $this->assertNotNull($ticket->sla_breached_at);
        Event::assertDispatched(SlaBreached::class);
    }

    public function test_closed_overdue_ticket_is_not_marked_breached(): void
    {
        $team = Team::query()->create(['name' => 'Support', 'slug' => 'support']);
        $ticket = Ticket::query()->create([
            'team_id' => $team->id,
            'type' => 'support_ticket',
            'source' => 'api',
            'subject' => 'Closed overdue',
            'status' => 'closed',
        ]);
        $ticket->updateQuietly(['sla_resolution_due_at' => now()->subHour()]);

        $this->artisan('sla:check-breaches');

        $this->assertNull($ticket->refresh()->sla_breached_at);
    }
}
