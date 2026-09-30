<?php

namespace Tests\Feature\Agent;

use App\Livewire\Agent\Team\TeamSettings;
use App\Livewire\Agent\TicketWorkspace;
use App\Models\Team;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class TicketAutoAssignTest extends TestCase
{
    use RefreshDatabase;

    private Team $team;

    private User $agent;

    protected function setUp(): void
    {
        parent::setUp();

        $this->team = Team::query()->create(['name' => 'Support', 'slug' => 'support']);
        $this->agent = User::factory()->create();
        $this->team->users()->attach($this->agent, ['role_in_team' => 'member']);
    }

    private function ticket(array $attributes = []): Ticket
    {
        return Ticket::query()->create([
            'team_id' => $this->team->id,
            'source' => 'api',
            'subject' => 'Druckerproblem',
            'requester_email' => 'kunde@example.com',
            ...$attributes,
        ]);
    }

    private function enableAutoAssign(): void
    {
        $this->team->forceFill(['auto_assign_on_view' => true])->save();
    }

    private function open(User $user, Ticket $ticket): void
    {
        Livewire::actingAs($user)->test(TicketWorkspace::class, ['ticket' => $ticket]);
    }

    public function test_opening_an_unassigned_ticket_assigns_it_when_enabled(): void
    {
        $this->enableAutoAssign();
        $ticket = $this->ticket();

        $this->open($this->agent, $ticket);

        $this->assertSame($this->agent->id, $ticket->fresh()->assigned_to);
    }

    public function test_nothing_happens_when_the_team_has_not_enabled_it(): void
    {
        $ticket = $this->ticket();

        $this->open($this->agent, $ticket);

        $this->assertNull($ticket->fresh()->assigned_to);
    }

    public function test_an_already_assigned_ticket_keeps_its_assignee(): void
    {
        $this->enableAutoAssign();
        $colleague = User::factory()->create();
        $this->team->users()->attach($colleague);
        $ticket = $this->ticket(['assigned_to' => $colleague->id]);

        $this->open($this->agent, $ticket);

        $this->assertSame($colleague->id, $ticket->fresh()->assigned_to);
    }

    public function test_a_closed_ticket_is_not_assigned(): void
    {
        $this->enableAutoAssign();
        $ticket = $this->ticket(['status' => 'closed', 'closed_at' => now()]);

        $this->open($this->agent, $ticket);

        $this->assertNull($ticket->fresh()->assigned_to);
    }

    public function test_a_viewer_outside_the_team_is_not_assigned(): void
    {
        $this->enableAutoAssign();
        Permission::findOrCreate('tickets.view.all', 'web');
        $supervisor = User::factory()->create();
        $supervisor->givePermissionTo('tickets.view.all');
        $ticket = $this->ticket();

        $this->open($supervisor, $ticket);

        $this->assertNull($ticket->fresh()->assigned_to);
    }

    public function test_team_admin_can_toggle_the_setting(): void
    {
        $admin = User::factory()->create();
        $this->team->users()->attach($admin, ['role_in_team' => 'team_admin']);

        Livewire::actingAs($admin)
            ->test(TeamSettings::class, ['team' => $this->team])
            ->assertSee('Tickets beim Öffnen automatisch zuweisen')
            ->call('toggleAutoAssign');

        $this->assertTrue($this->team->fresh()->auto_assign_on_view);
    }

    public function test_team_member_with_settings_access_cannot_toggle_the_setting(): void
    {
        Permission::findOrCreate('team.manage', 'web');
        $this->agent->givePermissionTo('team.manage');

        Livewire::actingAs($this->agent)
            ->test(TeamSettings::class, ['team' => $this->team])
            ->assertDontSee('Tickets beim Öffnen automatisch zuweisen')
            ->call('toggleAutoAssign')
            ->assertForbidden();

        $this->assertFalse($this->team->fresh()->auto_assign_on_view);
    }
}
