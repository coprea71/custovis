<?php

namespace Tests\Feature\Itil;

use App\Livewire\Admin\CmdbManager;
use App\Livewire\Admin\SlaManager;
use App\Livewire\Agent\ApprovalInbox;
use App\Livewire\Agent\TicketItilPanel;
use App\Models\CmdbConfigurationItem;
use App\Models\Team;
use App\Models\Ticket;
use App\Models\TicketChange;
use App\Models\TicketIncident;
use App\Models\User;
use App\Services\Itil\BusinessHoursCalendar;
use App\States\Change\Approved;
use App\States\Change\CabReview;
use App\States\Change\Draft;
use App\States\Incident\InProgress;
use App\States\Incident\New_;
use App\States\Incident\Resolved;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\TestCase;

class ItilUiTest extends TestCase
{
    use RefreshDatabase;

    private Team $team;

    private User $agent;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->team = Team::query()->create(['name' => 'Ops', 'slug' => 'ops']);
        $this->agent = User::factory()->create();
        $this->team->users()->attach($this->agent);
        $this->admin = User::factory()->create(['name' => 'CAB Chefin']);
        $this->admin->assignRole('system_admin');
    }

    public function test_incident_walks_through_allowed_states_only(): void
    {
        $ticket = $this->ticket('incident');
        TicketIncident::query()->create(['ticket_id' => $ticket->id, 'state' => New_::class]);

        $panel = Livewire::actingAs($this->agent)->test(TicketItilPanel::class, ['ticketId' => $ticket->id])
            ->assertSee('In Bearbeitung')
            ->call('changeState', InProgress::class)
            ->call('changeState', Resolved::class);

        $this->assertTrue($ticket->fresh()->incident->state->equals(Resolved::class));
        $panel->call('changeState', New_::class)->assertStatus(422);
    }

    public function test_change_goes_through_cab_inbox_to_approved(): void
    {
        $ticket = $this->ticket('change');
        TicketChange::query()->create(['ticket_id' => $ticket->id, 'state' => Draft::class]);

        Livewire::actingAs($this->agent)->test(TicketItilPanel::class, ['ticketId' => $ticket->id])
            ->call('changeState', CabReview::class)->assertStatus(422);
        Livewire::actingAs($this->agent)->test(TicketItilPanel::class, ['ticketId' => $ticket->id])
            ->set('approverIds', [$this->admin->id])->call('requestCab')->assertHasNoErrors();
        $this->assertTrue($ticket->fresh()->change->state->equals(CabReview::class));

        Livewire::actingAs($this->admin)->test(ApprovalInbox::class)
            ->assertSee($ticket->subject)
            ->set("comments.{$ticket->change->approvals()->first()->id}", 'Passt, Wartungsfenster ok.')
            ->call('decide', $ticket->change->approvals()->first()->id, true);

        $this->assertTrue($ticket->fresh()->change->state->equals(Approved::class));
        $this->assertDatabaseHas('cab_approvals', ['ticket_id' => $ticket->id, 'decision' => 'approved', 'comment' => 'Passt, Wartungsfenster ok.']);
    }

    public function test_sla_deadline_respects_business_hours_configured_in_admin(): void
    {
        Livewire::actingAs($this->admin)->test(SlaManager::class)
            ->call('selectTeam', $this->team->id)
            ->set('policies.normal', ['response' => 60, 'resolution' => 120])
            ->set('hours', [1 => ['start' => '08:00', 'end' => '18:00'], 2 => ['start' => '08:00', 'end' => '18:00'], 3 => ['start' => null, 'end' => null],
                4 => ['start' => null, 'end' => null], 5 => ['start' => '08:00', 'end' => '18:00'], 6 => ['start' => null, 'end' => null], 0 => ['start' => null, 'end' => null]])
            ->call('save')->assertHasNoErrors();

        Carbon::setTestNow('2026-09-25 17:00:00'); // Friday
        $ticket = $this->ticket('support_ticket');

        $this->assertSame('2026-09-28 09:00:00', $ticket->fresh()->sla_resolution_due_at->format('Y-m-d H:i:s'));
        $this->assertSame('2026-09-25 18:00:00', $ticket->fresh()->sla_response_due_at->format('Y-m-d H:i:s'));
    }

    public function test_team_without_business_hours_keeps_calendar_time(): void
    {
        $due = app(BusinessHoursCalendar::class)->addMinutes($this->team->id, Carbon::parse('2026-09-26 23:00:00'), 120);

        $this->assertSame('2026-09-27 01:00:00', $due->format('Y-m-d H:i:s'));
    }

    public function test_ci_is_created_in_cmdb_and_linked_to_incident(): void
    {
        Livewire::actingAs($this->admin)->test(CmdbManager::class)
            ->set('form', ['team_id' => $this->team->id, 'name' => 'mail01', 'type' => 'server', 'status' => 'active'])
            ->call('save')->assertHasNoErrors();
        $ci = CmdbConfigurationItem::query()->where('name', 'mail01')->firstOrFail();
        $ticket = $this->ticket('incident');
        TicketIncident::query()->create(['ticket_id' => $ticket->id, 'state' => New_::class]);

        Livewire::actingAs($this->agent)->test(TicketItilPanel::class, ['ticketId' => $ticket->id])
            ->set('ciId', $ci->id)->call('attachCi')->assertSee('mail01');

        $this->assertTrue($ticket->configurationItems()->whereKey($ci->id)->exists());
        Livewire::actingAs($this->agent)->test(CmdbManager::class)->assertForbidden();
    }

    private function ticket(string $type): Ticket
    {
        return Ticket::query()->create(['team_id' => $this->team->id, 'type' => $type, 'source' => 'api', 'subject' => "Test {$type}"]);
    }
}
