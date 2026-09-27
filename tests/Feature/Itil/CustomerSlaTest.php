<?php

namespace Tests\Feature\Itil;

use App\Livewire\Admin\CustomerSlaManager;
use App\Models\Customer;
use App\Models\SlaPolicy;
use App\Models\Team;
use App\Models\Ticket;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CustomerSlaTest extends TestCase
{
    use RefreshDatabase;

    private Team $team;

    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        $this->team = Team::query()->create(['name' => 'Support', 'slug' => 'support']);
        $this->customer = Customer::factory()->create(['email' => 'vip@example.com']);
        SlaPolicy::query()->create(['team_id' => $this->team->id, 'name' => 'High', 'priority' => 'high', 'response_time_minutes' => 240, 'resolution_time_minutes' => 1440]);
    }

    public function test_customer_sla_takes_precedence_over_team_sla(): void
    {
        $vip = SlaPolicy::query()->create(['customer_id' => $this->customer->id, 'name' => 'High', 'priority' => 'high', 'response_time_minutes' => 30, 'resolution_time_minutes' => 120]);

        $vipTicket = $this->ticket('VIP@example.com');
        $otherTicket = $this->ticket('other@example.com');

        $this->assertSame($this->customer->id, $vipTicket->customer_id, 'known sender is linked on creation, case-insensitive');
        $this->assertSame($vip->id, $vipTicket->sla_policy_id);
        $this->assertSame(30, (int) $vipTicket->created_at->diffInMinutes($vipTicket->sla_response_due_at));
        $this->assertNull($otherTicket->customer_id);
        $this->assertNotSame($vip->id, $otherTicket->sla_policy_id);
    }

    public function test_team_sla_applies_when_customer_has_none_for_the_priority(): void
    {
        SlaPolicy::query()->create(['customer_id' => $this->customer->id, 'name' => 'Urgent', 'priority' => 'urgent', 'response_time_minutes' => 15, 'resolution_time_minutes' => 60]);

        $ticket = $this->ticket('vip@example.com');

        $this->assertSame(240, (int) $ticket->created_at->diffInMinutes($ticket->sla_response_due_at));
    }

    public function test_admin_maintains_customer_sla(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('system_admin');

        Livewire::actingAs($admin)->test(CustomerSlaManager::class)
            ->call('selectCustomer', $this->customer->id)
            ->set('policies.high.response', '30')->call('save')->assertHasErrors(['policies.high.resolution'])
            ->set('policies.high.resolution', '120')->call('save')->assertHasNoErrors()
            ->assertSee('Kunden-SLA gilt');

        $this->assertDatabaseHas('sla_policies', ['customer_id' => $this->customer->id, 'priority' => 'high', 'team_id' => null, 'response_time_minutes' => 30]);
        $this->assertDatabaseHas('sla_policies', ['team_id' => $this->team->id, 'priority' => 'high', 'response_time_minutes' => 240]);

        Livewire::actingAs($admin)->test(CustomerSlaManager::class)
            ->call('selectCustomer', $this->customer->id)->assertSet('policies.high.response', 30)
            ->set('policies.high.response', '')->set('policies.high.resolution', '')->call('save');
        $this->assertDatabaseMissing('sla_policies', ['customer_id' => $this->customer->id]);
    }

    public function test_customer_sla_requires_permission(): void
    {
        Livewire::actingAs(User::factory()->create())->test(CustomerSlaManager::class)->assertForbidden();
    }

    private function ticket(string $email): Ticket
    {
        return Ticket::query()->create(['team_id' => $this->team->id, 'source' => 'mailbox', 'subject' => 'Ausfall',
            'requester_email' => $email, 'priority' => 'high'])->fresh();
    }
}
