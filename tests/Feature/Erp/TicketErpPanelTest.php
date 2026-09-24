<?php

namespace Tests\Feature\Erp;

use App\Livewire\Agent\TicketErpPanel;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class TicketErpPanelTest extends TestCase
{
    use CreatesErpFixtures;
    use RefreshDatabase;

    private function grantErpView(User $user): User
    {
        $user->givePermissionTo(Permission::findOrCreate('erp.customer.view', 'web'));

        return $user;
    }

    public function test_widget_is_hidden_and_forbidden_without_permission(): void
    {
        $team = $this->makeTeam();
        $ticket = $this->makeTicket($team);
        $user = $this->makeMember($team);

        $this->actingAs($user)->get('/agent/tickets/'.$ticket->id)
            ->assertOk()
            ->assertDontSee('ERP-Kundendaten');

        Livewire::actingAs($user)
            ->test(TicketErpPanel::class, ['ticketId' => $ticket->id])
            ->assertForbidden();
    }

    public function test_widget_is_visible_with_permission_and_does_not_call_erp_on_open(): void
    {
        Http::fake();
        $team = $this->makeTeam();
        $this->makeConnection($team);
        $ticket = $this->makeTicket($team);
        $user = $this->grantErpView($this->makeMember($team));

        $this->actingAs($user)->get('/agent/tickets/'.$ticket->id)
            ->assertOk()
            ->assertSee('Kundendaten laden');

        Http::assertNothingSent();
    }

    public function test_agent_loads_customer_data_for_ticket_requester(): void
    {
        $team = $this->makeTeam();
        $this->makeConnection($team);
        $this->fakeOdoo([['id' => 5, 'name' => 'Erika <b>Muster</b>', 'phone' => '+49 30 123']]);
        $user = $this->grantErpView($this->makeMember($team));

        Livewire::actingAs($user)
            ->test(TicketErpPanel::class, ['ticketId' => $this->makeTicket($team)->id])
            ->call('loadCustomer')
            ->assertSee('Erika &lt;b&gt;Muster&lt;/b&gt;', false)
            ->assertSee('+49 30 123');

        $this->assertDatabaseHas('audit_logs', ['action' => 'erp.customer.lookup', 'user_id' => $user->id, 'team_id' => $team->id]);
    }

    public function test_only_connections_of_the_ticket_team_are_used(): void
    {
        $team = $this->makeTeam();
        $otherTeam = $this->makeTeam('vertrieb');
        $this->makeConnection($otherTeam);
        $this->makeConnection($team, overrides: ['is_active' => false]);
        Http::fake();
        $user = $this->grantErpView($this->makeMember($team));
        $otherTeam->users()->attach($user, ['role_in_team' => 'member']);

        Livewire::actingAs($user)
            ->test(TicketErpPanel::class, ['ticketId' => $this->makeTicket($team)->id])
            ->call('loadCustomer')
            ->assertSee('Keine aktive ERP-Anbindung');

        Http::assertNothingSent();
    }

    public function test_agent_outside_ticket_team_is_forbidden(): void
    {
        $team = $this->makeTeam();
        $this->makeConnection($team);
        $outsider = $this->grantErpView(User::factory()->create());

        Livewire::actingAs($outsider)
            ->test(TicketErpPanel::class, ['ticketId' => $this->makeTicket($team)->id])
            ->call('loadCustomer')
            ->assertForbidden();

        $this->assertSame(0, AuditLog::query()->where('action', 'erp.customer.lookup')->count());
    }

    public function test_unreachable_erp_shows_hint_instead_of_failing(): void
    {
        $team = $this->makeTeam();
        $this->makeConnection($team);
        Http::fake(['odoo.example.com/*' => Http::failedConnection()]);
        $user = $this->grantErpView($this->makeMember($team));

        Livewire::actingAs($user)
            ->test(TicketErpPanel::class, ['ticketId' => $this->makeTicket($team)->id])
            ->call('loadCustomer')
            ->assertOk()
            ->assertSee('ERP derzeit nicht erreichbar');
    }
}
