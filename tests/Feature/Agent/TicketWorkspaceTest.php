<?php

namespace Tests\Feature\Agent;

use App\Livewire\Agent\TicketWorkspace;
use App\Models\Team;
use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TicketWorkspaceTest extends TestCase
{
    use RefreshDatabase;

    private Team $team;

    private function makeTicket(): Ticket
    {
        $team = $this->team = Team::query()->create(['name' => 'Support', 'slug' => 'support']);

        return Ticket::query()->create([
            'team_id' => $team->id,
            'type' => 'support_ticket',
            'source' => 'api',
            'subject' => 'Testticket',
            'requester_email' => 'kunde@example.com',
            'requester_name' => 'Kunde',
        ]);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/agent')->assertRedirect('/agent/login');
    }

    public function test_agent_can_see_ticket_list(): void
    {
        $user = User::factory()->create();
        $ticket = $this->makeTicket();
        $this->team->users()->attach($user);

        $this->actingAs($user)
            ->get('/agent')
            ->assertOk()
            ->assertSee($ticket->subject);
    }

    public function test_public_reply_and_internal_note_are_stored_with_correct_visibility(): void
    {
        $user = User::factory()->create();
        $ticket = $this->makeTicket();
        $this->team->users()->attach($user);

        Livewire::actingAs($user)
            ->test(TicketWorkspace::class, ['ticket' => $ticket])
            ->set('replyVisibility', TicketMessage::VISIBILITY_PUBLIC)
            ->set('replyBody', 'Öffentliche Antwort an den Kunden')
            ->call('sendReply')
            ->set('replyVisibility', TicketMessage::VISIBILITY_INTERNAL_NOTE)
            ->set('replyBody', 'Interne Notiz für Kollegen')
            ->call('sendReply');

        $this->assertDatabaseHas('ticket_messages', [
            'ticket_id' => $ticket->id,
            'visibility' => TicketMessage::VISIBILITY_PUBLIC,
            'body_text' => 'Öffentliche Antwort an den Kunden',
        ]);

        $this->assertDatabaseHas('ticket_messages', [
            'ticket_id' => $ticket->id,
            'visibility' => TicketMessage::VISIBILITY_INTERNAL_NOTE,
            'body_text' => 'Interne Notiz für Kollegen',
        ]);
    }

    public function test_status_filter_defaults_to_open(): void
    {
        $user = User::factory()->create();
        $open = $this->makeTicket();
        $this->team->users()->attach($user);
        $closed = Ticket::query()->create(['team_id' => $this->team->id, 'source' => 'api', 'subject' => 'Erledigt', 'status' => 'closed']);

        $ids = Livewire::actingAs($user)->test(TicketWorkspace::class)
            ->assertSet('statusFilter', 'open')
            ->viewData('tickets')->pluck('id')->all();

        $this->assertContains($open->id, $ids);
        $this->assertNotContains($closed->id, $ids);
    }

    public function test_tickets_are_sorted_by_priority_then_oldest_id_by_default(): void
    {
        $user = User::factory()->create();
        $normal = $this->makeTicket();
        $this->team->users()->attach($user);
        $highOld = Ticket::query()->create(['team_id' => $this->team->id, 'source' => 'api', 'subject' => 'A', 'priority' => 'high']);
        $urgent = Ticket::query()->create(['team_id' => $this->team->id, 'source' => 'api', 'subject' => 'B', 'priority' => 'urgent']);
        $highNew = Ticket::query()->create(['team_id' => $this->team->id, 'source' => 'api', 'subject' => 'C', 'priority' => 'high']);

        $component = Livewire::actingAs($user)->test(TicketWorkspace::class);

        $this->assertSame(
            [$urgent->id, $highOld->id, $highNew->id, $normal->id],
            $component->viewData('tickets')->pluck('id')->all()
        );

        $component->call('sortTickets', 'id');

        $this->assertSame(
            [$normal->id, $highOld->id, $urgent->id, $highNew->id],
            $component->viewData('tickets')->pluck('id')->all()
        );
    }

    public function test_unknown_sort_field_is_rejected(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)->test(TicketWorkspace::class)
            ->call('sortTickets', 'subject; drop table tickets')
            ->assertStatus(422);
    }

    public function test_ticket_header_shows_priority_and_status(): void
    {
        $user = User::factory()->create();
        $ticket = $this->makeTicket();
        $ticket->update(['priority' => 'high', 'status' => 'pending']);
        $this->team->users()->attach($user);

        Livewire::actingAs($user)->test(TicketWorkspace::class, ['ticket' => $ticket])
            ->assertSee('Hoch | Wartend');
    }
}
