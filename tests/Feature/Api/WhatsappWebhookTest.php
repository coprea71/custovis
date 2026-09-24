<?php

namespace Tests\Feature\Api;

use App\Models\Team;
use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Models\WhatsappAccount;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WhatsappWebhookTest extends TestCase
{
    use RefreshDatabase;

    private function makeAccount(): WhatsappAccount
    {
        $team = Team::query()->create(['name' => 'Support', 'slug' => 'support']);

        return WhatsappAccount::query()->create([
            'team_id' => $team->id,
            'display_name' => 'Support WA',
            'phone_number_id' => '1234567890',
            'business_account_id' => 'ba-1',
            'access_token' => 'token',
            'webhook_verify_token' => 'verify-me',
            'app_secret' => 'app-secret',
        ]);
    }

    private function messagePayload(string $waMessageId, string $from, string $body): array
    {
        return [
            'entry' => [[
                'changes' => [[
                    'value' => [
                        'contacts' => [['profile' => ['name' => 'Max Mustermann']]],
                        'messages' => [[
                            'id' => $waMessageId,
                            'from' => $from,
                            'type' => 'text',
                            'text' => ['body' => $body],
                        ]],
                    ],
                ]],
            ]],
        ];
    }

    private function postWhatsapp(WhatsappAccount $account, array $payload)
    {
        $body = json_encode($payload);
        $signature = 'sha256='.hash_hmac('sha256', $body, 'app-secret');

        return $this->call('POST', "/webhooks/whatsapp/{$account->id}", [], [], [], [
            'HTTP_X-Hub-Signature-256' => $signature,
            'CONTENT_TYPE' => 'application/json',
        ], $body);
    }

    public function test_verify_handshake_returns_challenge_for_correct_token(): void
    {
        $account = $this->makeAccount();

        $this->get("/webhooks/whatsapp/{$account->id}?hub_mode=subscribe&hub_verify_token=verify-me&hub_challenge=abc123")
            ->assertOk()
            ->assertSee('abc123');
    }

    public function test_verify_handshake_rejects_wrong_token(): void
    {
        $account = $this->makeAccount();

        $this->get("/webhooks/whatsapp/{$account->id}?hub_mode=subscribe&hub_verify_token=wrong&hub_challenge=abc123")
            ->assertForbidden();
    }

    public function test_invalid_signature_is_rejected(): void
    {
        $account = $this->makeAccount();
        $body = json_encode($this->messagePayload('wamid.1', '491701234567', 'Hallo'));

        $this->call('POST', "/webhooks/whatsapp/{$account->id}", [], [], [], [
            'HTTP_X-Hub-Signature-256' => 'sha256=invalid',
            'CONTENT_TYPE' => 'application/json',
        ], $body)->assertForbidden();
    }

    public function test_inbound_message_creates_ticket(): void
    {
        $account = $this->makeAccount();

        $this->postWhatsapp($account, $this->messagePayload('wamid.1', '491701234567', 'Hallo, ich brauche Hilfe'))
            ->assertNoContent();

        $this->assertDatabaseHas('tickets', [
            'source' => 'whatsapp',
            'requester_phone' => '491701234567',
            'whatsapp_account_id' => $account->id,
        ]);
    }

    public function test_second_message_from_same_contact_reuses_open_ticket(): void
    {
        $account = $this->makeAccount();

        $this->postWhatsapp($account, $this->messagePayload('wamid.1', '491701234567', 'Erste Nachricht'));
        $this->postWhatsapp($account, $this->messagePayload('wamid.2', '491701234567', 'Zweite Nachricht'));

        $this->assertSame(1, Ticket::query()->where('requester_phone', '491701234567')->count());
        $this->assertSame(2, Ticket::query()->where('requester_phone', '491701234567')->first()->messages()->count());
    }

    public function test_message_after_ticket_closed_reopens_instead_of_creating_new_ticket(): void
    {
        $account = $this->makeAccount();

        $this->postWhatsapp($account, $this->messagePayload('wamid.1', '491701234567', 'Erste Nachricht'));
        $ticket = Ticket::query()->where('requester_phone', '491701234567')->firstOrFail();
        $ticket->update(['status' => 'closed']);

        $this->postWhatsapp($account, $this->messagePayload('wamid.2', '491701234567', 'Nach dem Schließen'));

        $this->assertSame(1, Ticket::query()->where('requester_phone', '491701234567')->count());
        $ticket->refresh();
        $this->assertSame('reopened', $ticket->status);
    }

    public function test_duplicate_webhook_delivery_does_not_duplicate_message(): void
    {
        $account = $this->makeAccount();
        $payload = $this->messagePayload('wamid.1', '491701234567', 'Hallo');

        $this->postWhatsapp($account, $payload);
        $this->postWhatsapp($account, $payload);

        $this->assertSame(1, TicketMessage::query()->where('whatsapp_message_id', 'wamid.1')->count());
    }
}
