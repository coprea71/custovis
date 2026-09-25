<?php

namespace Tests\Feature\Erp;

use App\Livewire\Agent\TicketCustomerCreate;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class TicketCustomerCreateTest extends TestCase
{
    use CreatesErpFixtures;
    use RefreshDatabase;

    private function grant(User $user, string ...$permissions): User
    {
        foreach ($permissions as $permission) {
            $user->givePermissionTo(Permission::findOrCreate($permission, 'web'));
        }

        return $user;
    }

    public function test_without_erp_connection_create_customer_is_offered_instead_of_erp_lookup(): void
    {
        $team = $this->makeTeam();
        $ticket = $this->makeTicket($team);
        $user = $this->grant($this->makeMember($team), 'erp.customer.view', 'customers.manage');

        $this->actingAs($user)->get('/agent/tickets/'.$ticket->id)
            ->assertOk()
            ->assertDontSee('Kundendaten laden')
            ->assertSee('Kunde anlegen');
    }

    public function test_with_erp_connection_only_erp_lookup_is_offered(): void
    {
        $team = $this->makeTeam();
        $this->makeConnection($team);
        $ticket = $this->makeTicket($team);
        $user = $this->grant($this->makeMember($team), 'erp.customer.view', 'customers.manage');

        $this->actingAs($user)->get('/agent/tickets/'.$ticket->id)
            ->assertOk()
            ->assertSee('Kundendaten laden')
            ->assertDontSee('Kunde anlegen');
    }

    public function test_agent_creates_customer_from_ticket_and_ticket_is_linked(): void
    {
        $team = $this->makeTeam();
        $ticket = $this->makeTicket($team);
        $user = $this->grant($this->makeMember($team), 'customers.manage');

        Livewire::actingAs($user)
            ->test(TicketCustomerCreate::class, ['ticketId' => $ticket->id])
            ->call('openModal')
            ->assertSet('name', 'Kunde')
            ->assertSet('email', 'kunde@example.com')
            ->set('email', 'Korrigiert@Example.com')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('open', false)
            ->assertSee('Kunde angelegt');

        $customer = Customer::query()->where('email', 'korrigiert@example.com')->firstOrFail();
        $this->assertSame($customer->id, $ticket->fresh()->customer_id);
        $this->assertDatabaseHas('audit_logs', ['action' => 'customer.created', 'user_id' => $user->id]);
    }

    public function test_create_customer_requires_permission_and_team_membership(): void
    {
        $team = $this->makeTeam();
        $ticket = $this->makeTicket($team);

        Livewire::actingAs($this->makeMember($team))
            ->test(TicketCustomerCreate::class, ['ticketId' => $ticket->id])
            ->assertForbidden();

        Livewire::actingAs($this->grant(User::factory()->create(), 'customers.manage'))
            ->test(TicketCustomerCreate::class, ['ticketId' => $ticket->id])
            ->call('openModal')
            ->assertForbidden();
    }
}
