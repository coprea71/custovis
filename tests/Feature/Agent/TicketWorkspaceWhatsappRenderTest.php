<?php

namespace Tests\Feature\Agent;

use App\Livewire\Agent\TicketWorkspace;
use App\Models\Team;
use App\Models\Ticket;
use App\Models\User;
use App\Models\WhatsappAccount;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TicketWorkspaceWhatsappRenderTest extends TestCase
{
    use RefreshDatabase;

    public function test_whatsapp_ticket_renders_session_badge_and_template_picker_when_window_expired(): void
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
        $ticket = Ticket::query()->create([
            'team_id' => $team->id,
            'whatsapp_account_id' => $account->id,
            'type' => 'support_ticket',
            'source' => 'whatsapp',
            'subject' => 'WA-Test',
            'requester_phone' => '491701234567',
            'requester_name' => 'Max',
        ]);
        $incoming = $ticket->messages()->create([
            'visibility' => 'public',
            'direction' => 'incoming',
            'body_text' => 'Hallo',
            'message_id' => 'whatsapp-wamid.1',
            'whatsapp_message_id' => 'wamid.1',
        ]);
        $incoming->timestamps = false;
        $incoming->created_at = now()->subHours(30);
        $incoming->save();

        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(TicketWorkspace::class, ['ticket' => $ticket])
            ->assertSee('24-Std.-Fenster abgelaufen')
            ->assertSee('Genehmigte Vorlage wählen');
    }
}
