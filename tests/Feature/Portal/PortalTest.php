<?php

namespace Tests\Feature\Portal;

use App\Livewire\Portal\NewRequest;
use App\Livewire\Portal\TicketDetail;
use App\Models\Customer;
use App\Models\KnowledgeBaseCategory;
use App\Models\ServiceCatalogItem;
use App\Models\Team;
use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Models\User;
use App\Services\KnowledgeBaseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PortalTest extends TestCase
{
    use RefreshDatabase;

    private Team $team;

    private Customer $alice;

    private Customer $bob;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->team = Team::query()->create(['name' => 'Support', 'slug' => 'support']);
        $this->alice = Customer::factory()->create(['name' => 'Alice', 'email' => 'alice@example.com']);
        $this->bob = Customer::factory()->create(['name' => 'Bob', 'email' => 'bob@example.com']);
    }

    public function test_customer_logs_in_with_customer_guard_only(): void
    {
        $this->get('/portal')->assertRedirect('/portal/login');

        $this->post('/portal/login', ['email' => 'alice@example.com', 'password' => 'password'])
            ->assertRedirect('/portal');

        $this->assertAuthenticatedAs($this->alice, 'customer');
        $this->assertGuest('web');
    }

    public function test_customer_sees_only_own_tickets_and_cannot_open_foreign_ones_by_url(): void
    {
        $own = $this->ticket(['customer_id' => $this->alice->id, 'subject' => 'Alice-Ticket']);
        $byMail = $this->ticket(['requester_email' => 'alice@example.com', 'subject' => 'Alice-Mail-Ticket']);
        $foreign = $this->ticket(['customer_id' => $this->bob->id, 'subject' => 'Bob-Geheimnis']);

        $this->actingAs($this->alice, 'customer')->get('/portal')
            ->assertSee('Alice-Ticket')
            ->assertSee('Alice-Mail-Ticket')
            ->assertDontSee('Bob-Geheimnis');

        $this->actingAs($this->alice, 'customer')->get("/portal/tickets/{$own->id}")->assertOk();
        $this->actingAs($this->alice, 'customer')->get("/portal/tickets/{$byMail->id}")->assertOk();
        $this->actingAs($this->alice, 'customer')->get("/portal/tickets/{$foreign->id}")->assertNotFound();
    }

    public function test_agent_session_does_not_grant_portal_access(): void
    {
        $this->actingAs(User::factory()->create(), 'web')->get('/portal')->assertRedirect('/portal/login');
    }

    public function test_timeline_hides_internal_notes_and_reply_reopens_closed_ticket(): void
    {
        $ticket = $this->ticket(['customer_id' => $this->alice->id, 'status' => 'closed', 'closed_at' => now()]);
        $ticket->messages()->create(['visibility' => TicketMessage::VISIBILITY_PUBLIC, 'direction' => 'outgoing', 'body_text' => 'Öffentliche Antwort']);
        $ticket->messages()->create(['visibility' => TicketMessage::VISIBILITY_INTERNAL_NOTE, 'direction' => 'outgoing', 'body_text' => 'Interner Vermerk']);

        Livewire::actingAs($this->alice, 'customer')
            ->test(TicketDetail::class, ['ticket' => $ticket])
            ->assertSee('Öffentliche Antwort')
            ->assertDontSee('Interner Vermerk')
            ->set('reply', 'Problem besteht weiterhin')
            ->call('sendReply')
            ->assertHasNoErrors();

        $this->assertSame('reopened', $ticket->fresh()->status);
        $this->assertDatabaseHas('ticket_messages', ['ticket_id' => $ticket->id, 'author_customer_id' => $this->alice->id, 'direction' => 'incoming']);
    }

    public function test_new_request_from_catalog_creates_ticket_in_customer_context(): void
    {
        $item = ServiceCatalogItem::query()->create(['team_id' => $this->team->id, 'name' => 'Neuer Laptop', 'active' => true]);
        $inactive = ServiceCatalogItem::query()->create(['team_id' => $this->team->id, 'name' => 'Alt', 'active' => false]);

        Livewire::actingAs($this->alice, 'customer')->test(NewRequest::class)
            ->set('service_catalog_item_id', $inactive->id)
            ->set('description', 'Bitte')
            ->call('submit')
            ->assertHasErrors('service_catalog_item_id')
            ->set('service_catalog_item_id', $item->id)
            ->call('submit')
            ->assertRedirect();

        $ticket = Ticket::query()->where('subject', 'Neuer Laptop')->firstOrFail();
        $this->assertSame($this->alice->id, $ticket->customer_id);
        $this->assertSame('service_request', $ticket->type);
        $this->assertSame($this->team->id, $ticket->team_id);
        $this->assertSame('Bitte', $ticket->messages()->first()->body_text);
    }

    public function test_portal_knowledge_base_lists_only_public_articles(): void
    {
        $category = KnowledgeBaseCategory::query()->create(['name' => 'FAQ']);
        $service = app(KnowledgeBaseService::class);
        $admin = User::factory()->create();
        $public = $service->publish(null, ['category_id' => $category->id, 'title' => 'Passwort vergessen', 'body' => 'So gehts', 'visibility' => 'public'], $admin);
        $internal = $service->publish(null, ['category_id' => $category->id, 'title' => 'Passwort-Datenbank intern', 'body' => 'Geheim', 'visibility' => 'internal'], $admin);

        $this->actingAs($this->alice, 'customer')->get('/portal/kb')
            ->assertSee('Passwort vergessen')
            ->assertDontSee('Passwort-Datenbank intern');

        $this->actingAs($this->alice, 'customer')->get("/portal/kb/{$public->id}")->assertOk();
        $this->actingAs($this->alice, 'customer')->get("/portal/kb/{$internal->id}")->assertNotFound();
    }

    private function ticket(array $attributes): Ticket
    {
        return Ticket::query()->create($attributes + [
            'team_id' => $this->team->id,
            'source' => 'mailbox',
            'subject' => 'Ticket',
        ]);
    }
}
