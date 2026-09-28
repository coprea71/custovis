<?php

namespace Tests\Feature\Agent;

use App\Livewire\Agent\TicketWorkspace;
use App\Models\Team;
use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Models\TicketReadState;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TicketUnreadTest extends TestCase
{
    use RefreshDatabase;

    private Team $team;

    private User $agent;

    protected function setUp(): void
    {
        parent::setUp();

        $this->team = Team::query()->create(['name' => 'Support', 'slug' => 'support']);
        $this->agent = User::factory()->create();
        $this->team->users()->attach($this->agent);
    }

    private function ticketWithCustomerMessage(string $subject = 'Druckerproblem'): Ticket
    {
        $ticket = Ticket::query()->create([
            'team_id' => $this->team->id,
            'source' => 'api',
            'subject' => $subject,
            'requester_email' => 'kunde@example.com',
        ]);
        $this->customerWrites($ticket);

        return $ticket;
    }

    private function customerWrites(Ticket $ticket): TicketMessage
    {
        return $ticket->messages()->create([
            'visibility' => TicketMessage::VISIBILITY_PUBLIC,
            'direction' => 'incoming',
            'external_author_email' => 'kunde@example.com',
            'body_text' => 'Hilfe!',
        ]);
    }

    private function hasUnread(Ticket $ticket, User $user): bool
    {
        return Ticket::query()->unreadFor($user)->whereKey($ticket->id)->exists();
    }

    public function test_new_customer_message_is_unread_and_marked_in_the_list(): void
    {
        $this->ticketWithCustomerMessage();

        Livewire::actingAs($this->agent)->test(TicketWorkspace::class)
            ->assertSeeHtml('data-unread-dot')
            ->assertSee('Nur ungelesene (1)');
    }

    public function test_opening_a_ticket_marks_it_read_for_this_agent_only(): void
    {
        $ticket = $this->ticketWithCustomerMessage();
        $colleague = User::factory()->create();
        $this->team->users()->attach($colleague);

        Livewire::actingAs($this->agent)->test(TicketWorkspace::class)
            ->call('selectTicket', $ticket->id)
            ->assertSeeHtml('data-new-message');

        $this->assertFalse($this->hasUnread($ticket, $this->agent));
        $this->assertTrue($this->hasUnread($ticket, $colleague));
    }

    public function test_later_customer_reply_makes_the_ticket_unread_again(): void
    {
        $ticket = $this->ticketWithCustomerMessage();
        TicketReadState::markRead($this->agent, $ticket);

        $this->customerWrites($ticket);

        $this->assertTrue($this->hasUnread($ticket, $this->agent));
    }

    public function test_own_messages_never_count_as_unread(): void
    {
        $ticket = $this->ticketWithCustomerMessage();
        TicketReadState::markRead($this->agent, $ticket);

        $ticket->messages()->create([
            'visibility' => TicketMessage::VISIBILITY_INTERNAL_NOTE,
            'direction' => 'outgoing',
            'author_user_id' => $this->agent->id,
            'body_text' => 'Notiz',
        ]);

        $this->assertFalse($this->hasUnread($ticket, $this->agent));
    }

    public function test_unread_filter_shows_only_tickets_with_unread_messages(): void
    {
        $read = $this->ticketWithCustomerMessage('Gelesenes Ticket');
        TicketReadState::markRead($this->agent, $read);
        $this->ticketWithCustomerMessage('Ungelesenes Ticket');

        Livewire::actingAs($this->agent)->test(TicketWorkspace::class)
            ->call('toggleUnreadOnly')
            ->assertSee('Ungelesenes Ticket')
            ->assertDontSee('Gelesenes Ticket');
    }

    public function test_mark_unread_restores_the_latest_foreign_message_and_closes_the_ticket(): void
    {
        $ticket = $this->ticketWithCustomerMessage();

        Livewire::actingAs($this->agent)->test(TicketWorkspace::class)
            ->call('selectTicket', $ticket->id)
            ->call('markUnread')
            ->assertSet('ticketId', null);

        $this->assertTrue($this->hasUnread($ticket, $this->agent));
    }

    public function test_mark_all_read_clears_the_current_view(): void
    {
        $first = $this->ticketWithCustomerMessage();
        $second = $this->ticketWithCustomerMessage();

        Livewire::actingAs($this->agent)->test(TicketWorkspace::class)
            ->call('markAllRead')
            ->assertSee('Nur ungelesene (0)');

        $this->assertFalse($this->hasUnread($first, $this->agent));
        $this->assertFalse($this->hasUnread($second, $this->agent));
    }

    public function test_mark_all_read_does_not_touch_tickets_of_other_teams(): void
    {
        $foreignTeam = Team::query()->create(['name' => 'Vertrieb', 'slug' => 'vertrieb']);
        $foreign = Ticket::query()->create(['team_id' => $foreignTeam->id, 'source' => 'api', 'subject' => 'Fremd']);
        $this->customerWrites($foreign);

        Livewire::actingAs($this->agent)->test(TicketWorkspace::class)->call('markAllRead');

        $this->assertDatabaseMissing('ticket_read_states', ['ticket_id' => $foreign->id]);
    }
}
