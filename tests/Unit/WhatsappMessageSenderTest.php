<?php

namespace Tests\Unit;

use App\Exceptions\WhatsappSessionWindowExpiredException;
use App\Models\Team;
use App\Models\Ticket;
use App\Models\WhatsappAccount;
use App\Services\WhatsappMessageSender;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WhatsappMessageSenderTest extends TestCase
{
    use RefreshDatabase;

    private function makeTicket(): Ticket
    {
        $team = Team::query()->create(['name' => 'Support', 'slug' => 'support']);
        $account = WhatsappAccount::query()->create([
            'team_id' => $team->id,
            'display_name' => 'Support WA',
            'phone_number_id' => '1234567890',
            'business_account_id' => 'ba-1',
            'access_token' => 'token',
            'webhook_verify_token' => 'verify-me',
            'app_secret' => 'app-secret',
        ]);

        return Ticket::query()->create([
            'team_id' => $team->id,
            'whatsapp_account_id' => $account->id,
            'type' => 'support_ticket',
            'source' => 'whatsapp',
            'subject' => 'Test',
            'requester_phone' => '491701234567',
            'requester_name' => 'Max',
        ]);
    }

    public function test_free_text_send_within_24h_window_succeeds(): void
    {
        Http::fake(['graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.out.1']]], 200)]);

        $ticket = $this->makeTicket();
        $incoming = $ticket->messages()->create([
            'visibility' => 'public',
            'direction' => 'incoming',
            'body_text' => 'Hallo',
            'message_id' => 'whatsapp-wamid.in.1',
            'whatsapp_message_id' => 'wamid.in.1',
        ]);
        $incoming->timestamps = false;
        $incoming->created_at = now()->subHours(2);
        $incoming->save();

        $outgoing = $ticket->messages()->create([
            'visibility' => 'public',
            'direction' => 'outgoing',
            'body_text' => 'Antwort',
            'message_id' => 'custovis-out-1',
        ]);

        app(WhatsappMessageSender::class)->sendFreeText($outgoing);

        Http::assertSent(fn ($request) => str_contains($request->url(), 'graph.facebook.com'));
    }

    public function test_free_text_send_outside_24h_window_throws(): void
    {
        $ticket = $this->makeTicket();
        $incoming = $ticket->messages()->create([
            'visibility' => 'public',
            'direction' => 'incoming',
            'body_text' => 'Hallo',
            'message_id' => 'whatsapp-wamid.in.1',
            'whatsapp_message_id' => 'wamid.in.1',
        ]);
        $incoming->timestamps = false;
        $incoming->created_at = now()->subHours(30);
        $incoming->save();

        $outgoing = $ticket->messages()->create([
            'visibility' => 'public',
            'direction' => 'outgoing',
            'body_text' => 'Zu spät',
            'message_id' => 'custovis-out-2',
        ]);

        $this->expectException(WhatsappSessionWindowExpiredException::class);

        app(WhatsappMessageSender::class)->sendFreeText($outgoing);
    }
}
