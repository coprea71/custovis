<?php

namespace Tests\Feature\Mail;

use App\DataTransferObjects\IncomingMailMessageData;
use App\Jobs\SendTicketReplyJob;
use App\Livewire\Agent\Team\AutoMailManager;
use App\Livewire\Agent\TicketWorkspace;
use App\Models\Mailbox;
use App\Models\Team;
use App\Models\TeamMailLayout;
use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Models\User;
use App\Services\Dashboard\DashboardSnapshotService;
use App\Services\MailToTicketService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use Tests\TestCase;

class HttpErrorMailFilterTest extends TestCase
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
        $this->mailbox = Mailbox::factory()->create(['team_id' => $this->team->id, 'email_address' => 'support@team.example']);
    }

    public function test_error_mail_becomes_ticket_without_any_automatic_mail(): void
    {
        $this->enableFilter();

        $ticket = $this->import('Error 500 on /checkout');
        $ticket->update(['status' => 'closed']);

        $this->assertSame(500, $ticket->fresh()->error_mail_status);
        $this->assertSame(0, $ticket->messages()->where('automatic', true)->count());
    }

    public function test_without_filter_error_mail_is_a_normal_ticket(): void
    {
        $ticket = $this->import('Error 500 on /checkout');

        $this->assertNull($ticket->error_mail_status);
        $this->assertSame(1, $ticket->messages()->where('automatic', true)->count());
    }

    public function test_normal_mail_keeps_its_acknowledgement_with_filter_on(): void
    {
        $this->enableFilter();

        $ticket = $this->import('Bestellung 404 ist nicht angekommen');

        $this->assertNull($ticket->error_mail_status);
        $this->assertSame(1, $ticket->messages()->where('automatic', true)->count());
    }

    public function test_sender_list_limits_the_filter_including_subdomains(): void
    {
        $this->enableFilter("alerts@firma.de\nshop.example");

        $this->assertSame(503, $this->import('HTTP 503', 'robot@monitor.shop.example', 'm1@x')->error_mail_status);
        $this->assertSame(502, $this->import('502 Bad Gateway', 'alerts@firma.de', 'm2@x')->error_mail_status);
        $this->assertNull($this->import('Error 500', 'kunde@example.com', 'm3@x')->error_mail_status);
    }

    public function test_team_admin_configures_the_filter(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $admin = $this->member('team_admin');

        Livewire::actingAs($admin)->test(AutoMailManager::class, ['team' => $this->team])
            ->set('errorMailFilterEnabled', true)
            ->set('errorMailSenders', "Alerts@Firma.de\n@shop.example\n")
            ->call('save')->assertHasNoErrors()
            ->assertSet('errorMailSenders', "alerts@firma.de\nshop.example");

        $layout = TeamMailLayout::forTeam($this->team);
        $this->assertTrue($layout->error_mail_filter_enabled);
        $this->assertSame("alerts@firma.de\nshop.example", $layout->error_mail_senders);
    }

    public function test_invalid_sender_entry_is_rejected(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        Livewire::actingAs($this->member('team_admin'))->test(AutoMailManager::class, ['team' => $this->team])
            ->set('errorMailSenders', 'kein gültiger eintrag')
            ->call('save')->assertHasErrors('errorMailSenders');
    }

    public function test_dashboard_evaluates_error_mails_per_status_code(): void
    {
        $this->enableFilter();
        $this->import('Error 500', 'robot@shop.example', 'm1@x');
        $this->import('HTTP 503', 'robot@shop.example', 'm2@x')->update(['status' => 'closed']);
        $this->import('404 Not Found', 'robot@shop.example', 'm3@x');
        $this->import('Drucker defekt', 'kunde@example.com', 'm4@x');

        $data = app(DashboardSnapshotService::class)->refresh($this->team)->data['error_mails'];

        $this->assertSame(3, $data['total']);
        $this->assertSame(1, $data['client']);
        $this->assertSame(2, $data['server']);
        $this->assertSame(2, $data['open']);
        $this->assertSame(['HTTP 404' => 1, 'HTTP 500' => 1, 'HTTP 503' => 1], $data['by_status']);
    }

    public function test_ticket_list_can_be_filtered_to_error_mails(): void
    {
        $this->enableFilter();
        $this->import('Error 500 im Shop', 'robot@shop.example', 'm1@x');
        $this->import('Drucker defekt', 'kunde@example.com', 'm2@x');
        $user = $this->member('member');

        Livewire::withQueryParams(['error_mails' => 1, 'status' => 'unresolved'])
            ->actingAs($user)->test(TicketWorkspace::class)
            ->assertSee('Nur HTTP-Fehlermails')
            ->assertSee('Error 500 im Shop')
            ->assertDontSee('Drucker defekt')
            ->call('clearErrorMailFilter')
            ->assertSee('Drucker defekt');
    }

    public function test_agent_cannot_reply_to_error_mail_but_can_add_internal_note(): void
    {
        $this->enableFilter();
        $ticket = $this->import('Error 500 im Shop');

        Livewire::actingAs($this->member('member'))->test(TicketWorkspace::class, ['ticket' => $ticket])
            ->assertSet('replyVisibility', TicketMessage::VISIBILITY_INTERNAL_NOTE)
            ->assertDontSee('Öffentliche Antwort')
            ->set('replyVisibility', TicketMessage::VISIBILITY_PUBLIC)
            ->set('replyBody', 'Danke für die Meldung')
            ->call('sendReply')->assertHasErrors('replyBody')
            ->set('replyVisibility', TicketMessage::VISIBILITY_INTERNAL_NOTE)
            ->call('sendReply')->assertHasNoErrors();

        $this->assertSame(0, $ticket->messages()->where('visibility', TicketMessage::VISIBILITY_PUBLIC)->where('direction', 'outgoing')->count());
        $this->assertSame(1, $ticket->messages()->where('visibility', TicketMessage::VISIBILITY_INTERNAL_NOTE)->count());
        Queue::assertNotPushed(SendTicketReplyJob::class);
    }

    private function enableFilter(?string $senders = null): void
    {
        TeamMailLayout::forTeam($this->team)->fill([
            'error_mail_filter_enabled' => true,
            'error_mail_senders' => $senders,
        ])->save();
    }

    private function import(string $subject, string $from = 'robot@shop.example', string $messageId = 'msg-1@x'): Ticket
    {
        return app(MailToTicketService::class)->import($this->mailbox, new IncomingMailMessageData(
            messageId: $messageId,
            inReplyTo: null,
            references: [],
            subject: $subject,
            fromEmail: $from,
            fromName: 'Monitor',
            bodyHtml: null,
            bodyText: 'Stacktrace …',
            attachments: [],
        ))->ticket;
    }

    private function member(string $role): User
    {
        $user = User::factory()->create();
        $this->team->users()->attach($user, ['role_in_team' => $role]);

        return $user;
    }
}
