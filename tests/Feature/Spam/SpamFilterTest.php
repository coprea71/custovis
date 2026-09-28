<?php

namespace Tests\Feature\Spam;

use App\DataTransferObjects\IncomingMailMessageData;
use App\Livewire\Admin\SpamManager;
use App\Livewire\Agent\TicketWorkspace;
use App\Models\Mailbox;
use App\Models\SpamRule;
use App\Models\Team;
use App\Models\Ticket;
use App\Models\User;
use App\Notifications\NewTicketNotification;
use App\Services\MailToTicketService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class SpamFilterTest extends TestCase
{
    use RefreshDatabase;

    private Team $team;

    private User $agent;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->team = Team::query()->create(['name' => 'Support', 'slug' => 'support']);
        $this->agent = User::factory()->create(['notify_new_tickets' => true]);
        $this->agent->assignRole('agent');
        $this->team->users()->attach($this->agent);
    }

    private function mailbox(Team $team): Mailbox
    {
        return Mailbox::query()->create([
            'team_id' => $team->id,
            'name' => 'Inbox '.$team->slug,
            'email_address' => $team->slug.'@example.com',
            'imap_host' => 'imap.example.com',
            'imap_username' => $team->slug.'@example.com',
            'imap_password' => 'secret',
            'smtp_host' => 'smtp.example.com',
            'smtp_username' => $team->slug.'@example.com',
            'smtp_password' => 'secret',
        ]);
    }

    private function importMail(Mailbox $mailbox, string $from): Ticket
    {
        $message = app(MailToTicketService::class)->import($mailbox, new IncomingMailMessageData(
            messageId: 'msg-1@x',
            inReplyTo: null,
            references: [],
            subject: 'Gewinnspiel',
            fromEmail: $from,
            fromName: 'Spammer',
            bodyHtml: '<p>Klick</p>',
            bodyText: 'Klick',
            attachments: [],
        ));

        return Ticket::query()->withoutGlobalScopes()->findOrFail($message->ticket_id);
    }

    private function mailTicket(string $from = 'spam@junk.example'): Ticket
    {
        return Ticket::query()->create([
            'team_id' => $this->team->id,
            'source' => 'mailbox',
            'subject' => 'Gewinnspiel',
            'requester_email' => $from,
        ]);
    }

    public function test_agent_marks_ticket_as_spam_by_domain(): void
    {
        $ticket = $this->mailTicket();

        Livewire::actingAs($this->agent)->test(TicketWorkspace::class, ['ticket' => $ticket])
            ->assertSee('Der ganzen Domain')
            ->call('markSpam', SpamRule::TYPE_DOMAIN)
            ->assertSet('ticketId', null)
            ->assertDontSee('Gewinnspiel');

        $this->assertDatabaseHas('spam_rules', ['team_id' => $this->team->id, 'type' => 'domain', 'value' => 'junk.example', 'created_by' => $this->agent->id]);
        $this->assertNull(Ticket::query()->find($ticket->id));
        $this->assertNotNull(Ticket::query()->onlySpam()->find($ticket->id));
    }

    public function test_invalid_spam_type_is_rejected(): void
    {
        $ticket = $this->mailTicket();

        Livewire::actingAs($this->agent)->test(TicketWorkspace::class, ['ticket' => $ticket])
            ->call('markSpam', 'everything')
            ->assertStatus(422);

        $this->assertDatabaseCount('spam_rules', 0);
    }

    public function test_agent_cannot_mark_another_teams_ticket(): void
    {
        $other = Team::query()->create(['name' => 'Vertrieb', 'slug' => 'vertrieb']);
        $foreign = Ticket::query()->create(['team_id' => $other->id, 'source' => 'mailbox', 'subject' => 'Fremd', 'requester_email' => 'a@b.example']);

        Livewire::actingAs($this->agent)->test(TicketWorkspace::class)
            ->set('ticketId', $foreign->id)
            ->call('markSpam', SpamRule::TYPE_EMAIL)
            ->assertStatus(404);

        $this->assertNull($foreign->fresh()->spam_at);
    }

    public function test_mail_from_blocked_subdomain_becomes_spam_without_notification(): void
    {
        Notification::fake();
        SpamRule::query()->create(['team_id' => $this->team->id, 'type' => 'domain', 'value' => 'junk.example']);

        $ticket = $this->importMail($this->mailbox($this->team), 'bot@mail.junk.example');

        $this->assertNotNull($ticket->spam_at);
        Notification::assertNotSentTo($this->agent, NewTicketNotification::class);
    }

    public function test_rule_of_another_team_does_not_apply(): void
    {
        $other = Team::query()->create(['name' => 'Vertrieb', 'slug' => 'vertrieb']);
        SpamRule::query()->create(['team_id' => $other->id, 'type' => 'email', 'value' => 'kunde@shop.example']);

        $ticket = $this->importMail($this->mailbox($this->team), 'Kunde@Shop.example');

        $this->assertNull($ticket->spam_at);
    }

    public function test_domain_rule_does_not_match_lookalike_domain(): void
    {
        SpamRule::query()->create(['team_id' => $this->team->id, 'type' => 'domain', 'value' => 'junk.example']);

        $this->assertFalse(SpamRule::matches($this->team->id, 'a@notjunk.example'));
        $this->assertTrue(SpamRule::matches($this->team->id, 'a@junk.example'));
    }

    public function test_spam_folder_requires_permission(): void
    {
        $this->actingAs($this->agent)->get('/admin/spam')->assertForbidden();
    }

    public function test_admin_releases_spam_and_lifts_rule(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('system_admin');
        $ticket = $this->mailTicket();
        $ticket->update(['spam_at' => now()]);
        $rule = SpamRule::query()->create(['team_id' => $this->team->id, 'type' => 'email', 'value' => 'spam@junk.example']);

        Livewire::actingAs($admin)->test(SpamManager::class)
            ->assertSee('Gewinnspiel')
            ->call('release', $ticket->id)
            ->call('deleteRule', $rule->id);

        $this->assertNull($ticket->fresh()->spam_at);
        $this->assertDatabaseCount('spam_rules', 0);
    }

    public function test_prune_deletes_only_expired_spam_with_attachments(): void
    {
        Storage::fake('local');
        $expired = $this->mailTicket();
        $expired->update(['spam_at' => now()->subDays(31)]);
        Storage::disk('local')->put("ticket-attachments/{$expired->id}/a.txt", 'x');
        $recent = $this->mailTicket();
        $recent->update(['spam_at' => now()->subDays(5)]);
        $normal = $this->mailTicket();

        $this->artisan('spam:prune')->assertSuccessful();

        $this->assertNull(Ticket::query()->withoutGlobalScopes()->find($expired->id));
        Storage::disk('local')->assertMissing("ticket-attachments/{$expired->id}/a.txt");
        $this->assertNotNull(Ticket::query()->onlySpam()->find($recent->id));
        $this->assertNotNull(Ticket::query()->find($normal->id));
    }
}
