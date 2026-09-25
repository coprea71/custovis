<?php

namespace Tests\Feature\Agent;

use App\Livewire\Agent\CreateTicket;
use App\Livewire\Agent\Team\CannedResponseManager;
use App\Livewire\Agent\TicketPropertiesPanel;
use App\Livewire\Agent\TicketWorkspace;
use App\Models\CannedResponse;
use App\Models\Team;
use App\Models\Ticket;
use App\Models\User;
use App\States\Incident\New_;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TicketHandlingTest extends TestCase
{
    use RefreshDatabase;

    private Team $support;

    private Team $ops;

    private User $agent;

    private User $colleague;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->support = Team::query()->create(['name' => 'Support', 'slug' => 'support']);
        $this->ops = Team::query()->create(['name' => 'Ops', 'slug' => 'ops']);
        $this->agent = User::factory()->create(['name' => 'Anna']);
        $this->colleague = User::factory()->create(['name' => 'Ben']);
        $this->support->users()->attach([$this->agent->id, $this->colleague->id]);
    }

    public function test_agent_changes_status_priority_and_assignee(): void
    {
        $ticket = $this->ticket();

        Livewire::actingAs($this->agent)->test(TicketPropertiesPanel::class, ['ticketId' => $ticket->id])
            ->set('status', 'closed')->set('priority', 'urgent')->set('assigned_to', $this->colleague->id)
            ->call('save')->assertHasNoErrors()->assertDispatched('ticket-updated');

        $ticket->refresh();
        $this->assertSame(['closed', 'urgent', $this->colleague->id], [$ticket->status, $ticket->priority, $ticket->assigned_to]);
        $this->assertNotNull($ticket->closed_at);
        $this->assertDatabaseHas('audit_logs', ['action' => 'ticket.updated', 'user_id' => $this->agent->id]);
    }

    public function test_agent_reopening_closed_ticket_records_internal_note(): void
    {
        $this->freezeTime();

        $ticket = $this->ticket();
        $ticket->update(['status' => 'closed', 'closed_at' => now()]);

        Livewire::actingAs($this->agent)->test(TicketPropertiesPanel::class, ['ticketId' => $ticket->id])
            ->set('status', 'open')->call('save')->assertHasNoErrors();

        $this->assertNull($ticket->fresh()->closed_at);
        $this->assertDatabaseHas('ticket_messages', [
            'ticket_id' => $ticket->id,
            'visibility' => 'internal_note',
            'author_user_id' => $this->agent->id,
            'body_text' => 'Ticket am '.now()->format('d.m.Y H:i').' durch '.$this->agent->name.' wiedereröffnet.',
        ]);
    }

    public function test_foreign_team_and_non_member_assignee_are_rejected(): void
    {
        $ticket = $this->ticket();
        $outsider = User::factory()->create();

        Livewire::actingAs($this->agent)->test(TicketPropertiesPanel::class, ['ticketId' => $ticket->id])
            ->set('team_id', $this->ops->id)->call('save')->assertHasErrors('team_id')
            ->set('team_id', $this->support->id)->set('assigned_to', $outsider->id)->call('save')->assertHasErrors('assigned_to');

        $this->assertSame($this->support->id, $ticket->fresh()->team_id);
    }

    public function test_agent_creates_incident_manually_with_start_state(): void
    {
        Livewire::actingAs($this->agent)->test(CreateTicket::class)
            ->set('form.type', 'incident')->set('form.subject', 'Drucker brennt')
            ->set('form.requester_phone', '+49 30 1234')->set('form.body', 'Anruf von Frau Muster')
            ->call('save')->assertHasNoErrors()->assertRedirect();

        $ticket = Ticket::query()->where('subject', 'Drucker brennt')->firstOrFail();
        $this->assertSame(['manual', $this->support->id, $this->agent->id], [$ticket->source, $ticket->team_id, $ticket->assigned_to]);
        $this->assertTrue($ticket->incident->state->equals(New_::class));

        Livewire::actingAs($this->agent)->test(CreateTicket::class)
            ->set('form.team_id', $this->ops->id)->set('form.subject', 'x')->set('form.body', 'y')
            ->call('save')->assertHasErrors('form.team_id');
    }

    public function test_mine_filter_lists_only_own_open_tickets(): void
    {
        $this->ticket(['subject' => 'Meins', 'assigned_to' => $this->agent->id]);
        $this->ticket(['subject' => 'Fremd zugewiesen', 'assigned_to' => $this->colleague->id]);

        Livewire::actingAs($this->agent)->test(TicketWorkspace::class)
            ->call('setStatusFilter', 'mine')
            ->assertSee('Meins')->assertDontSee('Fremd zugewiesen');
    }

    public function test_team_admin_manages_canned_responses_members_only_read(): void
    {
        $this->support->users()->updateExistingPivot($this->agent->id, ['role_in_team' => 'team_admin']);

        Livewire::actingAs($this->agent)->test(CannedResponseManager::class, ['team' => $this->support])
            ->set('title', 'Gruß')->set('body', 'Viele Grüße')->call('save')->assertHasNoErrors();
        $this->assertDatabaseHas('canned_responses', ['team_id' => $this->support->id, 'title' => 'Gruß']);

        Livewire::actingAs($this->colleague)->test(CannedResponseManager::class, ['team' => $this->support])->assertForbidden();
        $this->assertSame(1, CannedResponse::query()->count());
    }

    private function ticket(array $attributes = []): Ticket
    {
        return Ticket::query()->create($attributes + ['team_id' => $this->support->id, 'source' => 'api', 'subject' => 'Ticket']);
    }
}
