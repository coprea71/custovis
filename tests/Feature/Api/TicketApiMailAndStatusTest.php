<?php

namespace Tests\Feature\Api;

use App\Jobs\SendTicketReplyJob;
use App\Livewire\Agent\Team\ApiKeyManager;
use App\Livewire\Agent\TicketWorkspace;
use App\Models\ApiClient;
use App\Models\Mailbox;
use App\Models\Team;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use Tests\TestCase;

class TicketApiMailAndStatusTest extends TestCase
{
    use RefreshDatabase;

    private Team $team;

    private Mailbox $mailbox;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        Queue::fake();
        $this->team = Team::query()->create(['name' => 'Support', 'slug' => 'support']);
        $this->mailbox = Mailbox::factory()->create(['team_id' => $this->team->id]);
    }

    public function test_api_ticket_with_mailbox_gets_acknowledgement_and_number(): void
    {
        $response = $this->createViaApi(['tickets.create'], $this->mailbox->id);

        $response->assertCreated()->assertJsonPath('data.status', 'open');
        $ticket = Ticket::query()->findOrFail($response->json('data.id'));
        $this->assertSame($this->mailbox->id, $ticket->mailbox_id);
        $ack = $ticket->messages()->where('automatic', true)->sole();
        Queue::assertPushed(SendTicketReplyJob::class, fn ($job) => $job->message->is($ack));
    }

    public function test_api_ticket_without_mailbox_sends_no_mail(): void
    {
        $this->createViaApi(['tickets.create'], null)->assertCreated();

        Queue::assertNothingPushed();
    }

    public function test_inactive_mailbox_is_not_used_as_sender(): void
    {
        $this->mailbox->update(['active' => false]);

        $this->createViaApi(['tickets.create'], $this->mailbox->id)->assertCreated();

        $this->assertNull(Ticket::query()->sole()->mailbox_id);
        Queue::assertNothingPushed();
    }

    public function test_agent_reply_to_api_ticket_is_mailed(): void
    {
        $ticketId = $this->createViaApi(['tickets.create'], $this->mailbox->id)->json('data.id');
        $agent = User::factory()->create();
        $this->team->users()->attach($agent);

        Livewire::actingAs($agent)->test(TicketWorkspace::class, ['ticket' => Ticket::query()->find($ticketId)])
            ->set('replyBody', 'Wir kümmern uns.')->call('sendReply');

        Queue::assertPushed(SendTicketReplyJob::class, fn ($job) => $job->message->body_text === 'Wir kümmern uns.');
    }

    public function test_status_of_own_ticket_can_be_read(): void
    {
        [$token] = $this->token(['tickets.create', 'tickets.view'], $this->mailbox->id);
        $id = $this->withToken($token)->postJson('/api/v1/tickets', $this->ticketData())->json('data.id');
        Ticket::query()->find($id)->update(['status' => 'in_progress']);

        $this->withToken($token)->getJson("/api/v1/tickets/{$id}")->assertOk()
            ->assertJsonPath('data.id', $id)
            ->assertJsonPath('data.status', 'in_progress')
            ->assertJsonPath('data.status_label', 'In Bearbeitung')
            ->assertJsonPath('data.closed_at', null);
    }

    public function test_status_of_foreign_team_ticket_is_not_found(): void
    {
        $other = Team::query()->create(['name' => 'Andere', 'slug' => 'andere']);
        $foreign = Ticket::query()->create(['team_id' => $other->id, 'source' => 'api', 'subject' => 'Geheim', 'requester_email' => 'x@example.com']);
        [$token] = $this->token(['tickets.create', 'tickets.view'], null);

        $this->withToken($token)->getJson("/api/v1/tickets/{$foreign->id}")->assertNotFound()->assertDontSee('Geheim');
    }

    public function test_status_requires_view_ability(): void
    {
        [$token] = $this->token(['tickets.create'], null);

        $this->withToken($token)->getJson('/api/v1/tickets/1')->assertForbidden();
    }

    public function test_new_key_gets_mailbox_and_view_ability_and_mailbox_can_be_changed(): void
    {
        $admin = User::factory()->create();
        $this->team->users()->attach($admin, ['role_in_team' => 'team_admin']);
        $foreignMailbox = Mailbox::factory()->create();

        $component = Livewire::actingAs($admin)->test(ApiKeyManager::class, ['team' => $this->team])
            ->assertSet('mailboxId', $this->mailbox->id)
            ->set('newClientName', 'Webshop')->call('createClient')->assertHasNoErrors();

        $client = ApiClient::query()->sole();
        $this->assertSame($this->mailbox->id, $client->mailbox_id);
        $this->assertEqualsCanonicalizing(['tickets.create', 'tickets.view'], $client->tokens()->sole()->abilities);

        $component->call('saveMailbox', $client->id, '');
        $this->assertNull($client->fresh()->mailbox_id);
        $component->call('saveMailbox', $client->id, (string) $foreignMailbox->id)->assertStatus(422);
        $this->assertNull($client->fresh()->mailbox_id);
    }

    private function createViaApi(array $abilities, ?int $mailboxId)
    {
        [$token] = $this->token($abilities, $mailboxId);

        return $this->withToken($token)->postJson('/api/v1/tickets', $this->ticketData());
    }

    /**
     * @return array{0: string, 1: ApiClient}
     */
    private function token(array $abilities, ?int $mailboxId): array
    {
        $client = ApiClient::query()->create(['team_id' => $this->team->id, 'mailbox_id' => $mailboxId, 'name' => 'Shop',
            'created_by' => User::factory()->create()->id]);

        return [$client->createToken('test', $abilities)->plainTextToken, $client];
    }

    private function ticketData(): array
    {
        return ['subject' => 'Drucker defekt', 'body' => "Zeile 1\nZeile 2", 'requester_email' => 'kunde@example.com', 'requester_name' => 'Max Kunde'];
    }
}
