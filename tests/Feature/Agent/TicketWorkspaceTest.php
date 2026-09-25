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
}
