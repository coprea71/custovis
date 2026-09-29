<?php

namespace Tests\Feature\Agent;

use App\Livewire\Agent\Chat\ChatWorkspace;
use App\Livewire\Agent\TicketPresence;
use App\Models\Team;
use App\Models\Ticket;
use App\Models\User;
use App\Support\Realtime;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class RealtimeFallbackTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_config_is_read_at_runtime_and_never_contains_the_secret(): void
    {
        $this->assertNull(Realtime::clientConfig());

        config([
            'broadcasting.default' => 'reverb',
            'broadcasting.connections.reverb.key' => 'public-key',
            'broadcasting.connections.reverb.secret' => 'top-secret',
            'broadcasting.connections.reverb.options' => ['host' => 'ws.example.org', 'port' => '443', 'scheme' => 'https'],
        ]);

        $this->assertSame(['key' => 'public-key', 'host' => 'ws.example.org', 'port' => 443, 'scheme' => 'https'], Realtime::clientConfig());
    }

    public function test_reverb_without_key_counts_as_disabled(): void
    {
        config(['broadcasting.default' => 'reverb', 'broadcasting.connections.reverb.key' => '']);

        $this->assertFalse(Realtime::enabled());
    }

    public function test_agents_see_each_other_on_the_same_ticket_without_websockets(): void
    {
        [$ticket, $team] = $this->ticket();
        $anna = $this->agent($team, 'Anna');
        $olaf = $this->agent($team, 'Olaf');

        Livewire::actingAs($anna)->test(TicketPresence::class, ['ticketId' => $ticket->id])
            ->assertDontSee('ebenfalls an');

        Livewire::actingAs($olaf)->test(TicketPresence::class, ['ticketId' => $ticket->id])
            ->assertSee('Anna schaut sich dieses Ticket ebenfalls an');
    }

    public function test_presence_of_foreign_tickets_is_forbidden(): void
    {
        [$ticket] = $this->ticket();
        $stranger = User::factory()->create();

        Livewire::actingAs($stranger)->test(TicketPresence::class, ['ticketId' => $ticket->id])
            ->assertForbidden();
    }

    public function test_chat_registers_no_echo_listeners_without_realtime(): void
    {
        $this->assertSame([], (new ChatWorkspace)->getListeners());
    }

    public function test_layout_tells_the_frontend_that_realtime_is_off(): void
    {
        [, $team] = $this->ticket();

        $this->actingAs($this->agent($team, 'Anna'))->get('/agent/tickets')
            ->assertOk()
            ->assertSee('window.custovisRealtime = null', false);
    }

    /**
     * @return array{Ticket, Team}
     */
    private function ticket(): array
    {
        $team = Team::query()->create(['name' => 'Support', 'slug' => 'support']);
        $ticket = Ticket::query()->create(['team_id' => $team->id, 'type' => 'support_ticket', 'source' => 'api', 'subject' => 'Test']);

        return [$ticket, $team];
    }

    private function agent(Team $team, string $name): User
    {
        $user = User::factory()->create(['name' => $name]);
        $team->users()->attach($user);

        return $user;
    }
}
