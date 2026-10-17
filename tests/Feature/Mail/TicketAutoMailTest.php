<?php

namespace Tests\Feature\Mail;

use App\DataTransferObjects\IncomingMailMessageData;
use App\Jobs\SendTicketReplyJob;
use App\Livewire\Admin\CustomerManager;
use App\Livewire\Agent\Team\AutoMailManager;
use App\Livewire\Agent\Team\TeamSettings;
use App\Livewire\Agent\TicketPropertiesPanel;
use App\Mail\TicketReplyMail;
use App\Models\Customer;
use App\Models\Mailbox;
use App\Models\Team;
use App\Models\TeamMailLayout;
use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Models\User;
use App\Services\Compliance\GdprService;
use App\Services\MailToTicketService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use Tests\TestCase;

class TicketAutoMailTest extends TestCase
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

    public function test_new_mail_ticket_gets_an_acknowledgement(): void
    {
        $ticket = $this->import('kunde@example.com')->ticket;

        $ack = $ticket->messages()->where('automatic', true)->sole();
        $this->assertSame('outgoing', $ack->direction);
        $this->assertStringContainsString('Guten Tag Max Kunde', $ack->body_text);
        $this->assertStringContainsString('vielen Dank für Ihre Nachricht', $ack->body_text);
        $this->assertStringContainsString((string) $ticket->id, $ack->body_text);
        Queue::assertPushed(SendTicketReplyJob::class, fn ($job) => $job->message->is($ack));
    }

    public function test_reply_to_existing_ticket_gets_no_second_acknowledgement(): void
    {
        $first = $this->import('kunde@example.com');
        $this->import('kunde@example.com', 'msg-2@customer.example', inReplyTo: $first->message_id);

        $this->assertSame(1, $first->ticket->messages()->where('automatic', true)->count());
    }

    public function test_robots_and_own_mailbox_get_no_acknowledgement(): void
    {
        $this->import('MAILER-DAEMON@example.com', 'msg-a@x');
        $this->import('noreply@shop.example', 'msg-b@x');
        $this->import('support@team.example', 'msg-c@x');

        Queue::assertNothingPushed();
    }

    public function test_team_can_switch_off_and_reword_the_acknowledgement(): void
    {
        TeamMailLayout::forTeam($this->team)->fill(['acknowledgement_text' => 'Danke {requester_name}, Ticket {ticket_id}.'])->save();
        $ticket = $this->import('kunde@example.com')->ticket;
        $this->assertSame('Danke Max Kunde, Ticket '.$ticket->id.'.', $ticket->messages()->where('automatic', true)->sole()->body_text);

        TeamMailLayout::forTeam($this->team)->fill(['acknowledgement_enabled' => false])->save();
        $this->import('kunde@example.com', 'msg-9@customer.example');
        $this->assertSame(1, Ticket::query()->withCount(['messages' => fn ($q) => $q->where('automatic', true)])->get()->sum('messages_count'));
    }

    public function test_closing_a_ticket_sends_the_resolved_mail(): void
    {
        $ticket = $this->ticket();

        $ticket->update(['status' => 'closed', 'closed_at' => now()]);

        $mail = $ticket->messages()->where('automatic', true)->sole();
        $this->assertStringContainsString('wurde gelöst', $mail->body_text);
        $this->assertStringContainsString('„Drucker defekt“', $mail->body_text);
    }

    public function test_status_change_mail_needs_team_and_customer_opt_in(): void
    {
        $ticket = $this->ticket();
        $customer = Customer::factory()->create(['email' => 'kunde@example.com', 'notify_status_changes' => true]);
        $ticket->update(['customer_id' => $customer->id]);

        $ticket->update(['status' => 'in_progress']);
        $this->assertSame(0, $ticket->messages()->where('automatic', true)->count(), 'team switch is off by default');

        TeamMailLayout::forTeam($this->team)->fill(['status_change_enabled' => true])->save();
        $ticket->update(['status' => 'pending']);
        $this->assertStringContainsString('Neuer Status: Wartend', $ticket->messages()->where('automatic', true)->sole()->body_text);

        $customer->update(['notify_status_changes' => false]);
        $ticket->refresh()->update(['status' => 'open']);
        $this->assertSame(1, $ticket->messages()->where('automatic', true)->count());
    }

    public function test_automatic_mail_tells_autoresponders_not_to_answer(): void
    {
        $ack = $this->import('kunde@example.com')->ticket->messages()->where('automatic', true)->sole();

        $headers = (new TicketReplyMail($ack))->headers();

        $this->assertSame('auto-generated', $headers->text['Auto-Submitted']);
    }

    public function test_customer_can_opt_out_of_acknowledgement_and_resolved_mail(): void
    {
        Customer::factory()->create(['email' => 'kunde@example.com', 'notify_acknowledgement' => false, 'notify_resolved' => false]);

        $ticket = $this->import('kunde@example.com')->ticket;
        $ticket->update(['status' => 'closed', 'closed_at' => now()]);

        $this->assertSame(0, $ticket->messages()->where('automatic', true)->count());
    }

    public function test_muted_ticket_gets_no_resolved_or_status_mail(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $ticket = $this->ticket();
        $agent = $this->member('member');
        $agent->assignRole('agent');

        Livewire::actingAs($agent)->test(TicketPropertiesPanel::class, ['ticketId' => $ticket->id])
            ->assertSee('Keine automatischen Mails')
            ->set('auto_mails_muted', true)
            ->set('status', 'closed')->assertHasNoErrors();

        $this->assertTrue($ticket->fresh()->auto_mails_muted);
        $this->assertSame('closed', $ticket->fresh()->status);
        $this->assertSame(0, $ticket->messages()->where('automatic', true)->count());
    }

    public function test_gdpr_anonymization_also_wipes_automatic_mails(): void
    {
        $ticket = $this->import('kunde@example.com')->ticket;

        app(GdprService::class)->anonymizeTicket($ticket);

        $this->assertStringNotContainsString('Max Kunde', $ticket->messages()->where('automatic', true)->sole()->body_text);
    }

    public function test_team_admin_rewords_auto_mails_and_default_text_is_not_stored(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $admin = $this->member('team_admin');

        Livewire::actingAs($admin)->test(AutoMailManager::class, ['team' => $this->team])
            ->set('enabled.status_change', true)
            ->set('texts.resolved', 'Erledigt: {ticket_subject}')
            ->call('save')->assertHasNoErrors();

        $layout = TeamMailLayout::forTeam($this->team);
        $this->assertTrue($layout->status_change_enabled);
        $this->assertSame('Erledigt: {ticket_subject}', $layout->resolved_text);
        $this->assertNull($layout->acknowledgement_text);
    }

    public function test_central_admin_configures_any_team_and_can_lock_out_team_admins(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $admin = User::factory()->create();
        $admin->assignRole('system_admin');
        $teamAdmin = $this->member('team_admin');

        Livewire::actingAs($admin)->test(AutoMailManager::class, ['team' => $this->team])
            ->assertSee('Team-Admins dürfen diese Seite sehen und bearbeiten')
            ->set('texts.acknowledgement', 'Zentral vorgegeben')->call('save')->assertHasNoErrors()
            ->call('toggleTeamAdminAccess');

        $this->assertSame('Zentral vorgegeben', TeamMailLayout::forTeam($this->team)->acknowledgement_text);
        $this->assertFalse($this->team->fresh()->auto_mails_team_admin_access);
        Livewire::actingAs($teamAdmin)->test(AutoMailManager::class, ['team' => $this->team->fresh()])->assertForbidden();
        Livewire::actingAs($teamAdmin)->test(TeamSettings::class, ['team' => $this->team->fresh()])->assertDontSee('Automatische E-Mails');
    }

    public function test_team_admin_cannot_change_team_admin_access(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        Livewire::actingAs($this->member('team_admin'))->test(AutoMailManager::class, ['team' => $this->team])
            ->assertDontSee('Team-Admins dürfen diese Seite sehen und bearbeiten')
            ->call('toggleTeamAdminAccess')->assertForbidden();

        $this->assertTrue($this->team->fresh()->auto_mails_team_admin_access);
    }

    public function test_team_members_without_admin_role_cannot_change_auto_mails(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        Livewire::actingAs($this->member('member'))->test(AutoMailManager::class, ['team' => $this->team])->assertForbidden();
    }

    public function test_customer_status_mail_switch_is_saved_in_customer_management(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $admin = User::factory()->create();
        $admin->assignRole('system_admin');
        $customer = Customer::factory()->create(['email' => 'kunde@example.com']);
        $this->assertFalse($customer->fresh()->notify_status_changes);

        Livewire::actingAs($admin)->test(CustomerManager::class)
            ->call('edit', $customer->id)->set('notify_status_changes', true)
            ->call('save')->assertHasNoErrors();

        $this->assertTrue($customer->fresh()->notify_status_changes);
    }

    private function import(string $from, string $messageId = 'msg-1@customer.example', ?string $inReplyTo = null): TicketMessage
    {
        return app(MailToTicketService::class)->import($this->mailbox, new IncomingMailMessageData(
            messageId: $messageId,
            inReplyTo: $inReplyTo,
            references: array_filter([$inReplyTo]),
            subject: 'Drucker defekt',
            fromEmail: $from,
            fromName: 'Max Kunde',
            bodyHtml: null,
            bodyText: 'Hilfe!',
            attachments: [],
        ));
    }

    private function ticket(): Ticket
    {
        return Ticket::query()->create(['team_id' => $this->team->id, 'mailbox_id' => $this->mailbox->id, 'source' => 'mailbox',
            'subject' => 'Drucker defekt', 'requester_email' => 'kunde@example.com', 'requester_name' => 'Max Kunde']);
    }

    private function member(string $role): User
    {
        $user = User::factory()->create();
        $this->team->users()->attach($user, ['role_in_team' => $role]);

        return $user;
    }
}
