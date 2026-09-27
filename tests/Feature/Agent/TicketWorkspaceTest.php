<?php

namespace Tests\Feature\Agent;

use App\Livewire\Agent\TicketCustomerContact;
use App\Livewire\Agent\TicketWorkspace;
use App\Models\AuditLog;
use App\Models\Customer;
use App\Models\Team;
use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
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

    public function test_ticket_list_polls_and_shows_newly_arrived_tickets(): void
    {
        $user = User::factory()->create();
        $this->makeTicket();
        $this->team->users()->attach($user);

        $component = Livewire::actingAs($user)
            ->test(TicketWorkspace::class)
            ->assertSeeHtml('wire:poll.30s')
            ->assertDontSee('Neu eingegangen');

        Ticket::query()->create([
            'team_id' => $this->team->id,
            'type' => 'support_ticket',
            'source' => 'email',
            'subject' => 'Neu eingegangen',
            'requester_email' => 'neu@example.com',
            'requester_name' => 'Neukunde',
        ]);

        $component->call('$refresh')->assertSee('Neu eingegangen');
    }

    public function test_sidebar_offers_linked_customer_contact_details_behind_info_button(): void
    {
        $user = User::factory()->create();
        $ticket = $this->makeTicket();
        $this->team->users()->attach($user);
        $customer = Customer::factory()->create(['phone' => '030 123456', 'mobile' => '0170 9876', 'street' => 'Hauptstr. 1',
            'postal_code' => '10115', 'city' => 'Berlin', 'notes' => 'Rückruf nur vormittags']);
        $ticket->update(['customer_id' => $customer->id]);

        Livewire::actingAs($user)->test(TicketWorkspace::class, ['ticket' => $ticket])
            ->assertSeeHtml('aria-label="Kontaktdaten anzeigen"')
            ->assertSeeHtml('x-show="customerInfo"')
            ->assertSee(['030 123456', '0170 9876', 'Hauptstr. 1, 10115 Berlin', 'Rückruf nur vormittags']);
    }

    public function test_ticket_list_shows_priority_colors_and_emergency_alert(): void
    {
        $user = User::factory()->create();
        $ticket = $this->makeTicket();
        $this->team->users()->attach($user);
        $ticket->update(['priority' => 'urgent']);
        Ticket::query()->create(['team_id' => $this->team->id, 'source' => 'api', 'subject' => 'Niedrig', 'priority' => 'low']);

        Livewire::actingAs($user)->test(TicketWorkspace::class)
            ->assertSeeHtml('border-l-red-600')
            ->assertSeeHtml('border-l-slate-400')
            ->assertDontSeeHtml('data-emergency-alert')
            ->assertSee(['Priorität: Dringend', 'Priorität: Niedrig']);

        Ticket::query()->create(['team_id' => $this->team->id, 'source' => 'api', 'subject' => 'Rechenzentrum brennt', 'priority' => 'emergency']);

        Livewire::actingAs($user)->test(TicketWorkspace::class)
            ->assertSeeHtml('ring-red-600')
            ->assertSeeHtml('data-emergency-alert')
            ->assertSee('Priorität: Notfall');
    }

    public function test_missing_contact_details_can_be_added_from_the_ticket(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $user = User::factory()->create();
        $user->givePermissionTo('customers.manage');
        $ticket = $this->makeTicket();
        $this->team->users()->attach($user);
        $customer = Customer::factory()->create();
        $ticket->update(['customer_id' => $customer->id]);

        Livewire::actingAs($user)->test(TicketCustomerContact::class, ['ticketId' => $ticket->id])
            ->assertSee(['Keine Kontaktdaten hinterlegt.', 'Kontaktdaten hinterlegen'])
            ->call('edit')->set('phone', '030<script>')->call('save')->assertHasErrors(['phone' => 'regex'])
            ->set('phone', '030 123456')->set('city', 'Berlin')->set('notes', '  ')->call('save')->assertHasNoErrors()
            ->assertSee(['030 123456', 'Berlin', 'Kontaktdaten bearbeiten']);

        $this->assertSame(['030 123456', 'Berlin', null], array_values($customer->fresh()->only(['phone', 'city', 'notes'])));
        $this->assertSame(['fields_changed' => ['phone', 'city']], AuditLog::query()->where('action', 'customer.updated')->sole()->meta);
    }

    public function test_agents_without_customer_permission_cannot_add_contact_details(): void
    {
        $user = User::factory()->create();
        $ticket = $this->makeTicket();
        $this->team->users()->attach($user);
        $ticket->update(['customer_id' => Customer::factory()->create()->id]);

        Livewire::actingAs($user)->test(TicketCustomerContact::class, ['ticketId' => $ticket->id])
            ->assertDontSee('Kontaktdaten hinterlegen')
            ->call('edit')->assertForbidden();
    }

    public function test_sidebar_has_no_info_button_without_linked_customer(): void
    {
        $user = User::factory()->create();
        $ticket = $this->makeTicket();
        $this->team->users()->attach($user);

        Livewire::actingAs($user)->test(TicketWorkspace::class, ['ticket' => $ticket])
            ->assertDontSeeHtml('aria-label="Kontaktdaten anzeigen"');
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

    public function test_selected_ticket_is_cleared_when_filter_hides_it(): void
    {
        $user = User::factory()->create();
        $ticket = $this->makeTicket();
        $this->team->users()->attach($user);

        Livewire::actingAs($user)->test(TicketWorkspace::class, ['ticket' => $ticket])
            ->set('replyBody', 'Entwurf')
            ->call('setStatusFilter', 'open')
            ->assertSet('ticketId', $ticket->id)
            ->call('setStatusFilter', 'closed')
            ->assertSet('ticketId', null)
            ->assertSet('replyBody', '')
            ->assertSee('Ticket auswählen')
            ->call('selectTicket', $ticket->id)
            ->set('search', 'gibt es nicht')
            ->assertSet('ticketId', null);
    }
}
