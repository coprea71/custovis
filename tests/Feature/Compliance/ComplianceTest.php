<?php

namespace Tests\Feature\Compliance;

use App\Livewire\Admin\ComplianceCenter;
use App\Livewire\Agent\TicketWorkspace;
use App\Models\AuditLog;
use App\Models\Customer;
use App\Models\Mailbox;
use App\Models\Setting;
use App\Models\Team;
use App\Models\Ticket;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class ComplianceTest extends TestCase
{
    use RefreshDatabase;

    private Team $team;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->team = Team::query()->create(['name' => 'Support', 'slug' => 'support']);
    }

    public function test_public_self_registration_is_disabled(): void
    {
        $this->post('/register', ['name' => 'X', 'email' => 'x@example.com', 'password' => 'secret123!', 'password_confirmation' => 'secret123!'])
            ->assertNotFound();
        $this->assertDatabaseMissing('users', ['email' => 'x@example.com']);
    }

    public function test_agents_only_see_tickets_of_their_teams(): void
    {
        $other = Team::query()->create(['name' => 'Ops', 'slug' => 'ops']);
        $own = $this->ticket(['subject' => 'Eigenes Ticket']);
        $foreign = $this->ticket(['team_id' => $other->id, 'subject' => 'Fremdes Ticket']);
        $agent = $this->agent();

        $this->actingAs($agent)->get('/agent')->assertSee('Eigenes Ticket')->assertDontSee('Fremdes Ticket');
        $this->actingAs($agent)->get("/agent/tickets/{$foreign->id}")->assertForbidden();
        Livewire::actingAs($agent)->test(TicketWorkspace::class)->call('selectTicket', $foreign->id)->assertDontSee('Fremdes Ticket');

        $admin = User::factory()->create();
        $admin->assignRole('system_admin');
        $this->actingAs($admin)->get('/agent')->assertSee('Fremdes Ticket');
        $this->assertNotNull($own);
    }

    public function test_two_factor_is_mandatory_in_production_only(): void
    {
        $agent = $this->agent();

        $this->actingAs($agent)->get('/agent')->assertOk();

        $this->app['env'] = 'production';
        $this->actingAs($agent)->get('/agent')->assertRedirect('/account/security');
        $this->actingAs($agent)->get('/account/security')->assertOk()->assertSee('verpflichtend');

        $agent->forceFill(['two_factor_secret' => encrypt('x'), 'two_factor_confirmed_at' => now()])->save();
        $this->actingAs($agent)->get('/agent')->assertOk();
        $this->actingAs($agent)->withSession(['auth.password_confirmed_at' => time()])
            ->withoutMiddleware(ValidateCsrfToken::class)
            ->delete('/user/two-factor-authentication')->assertForbidden();
        $this->assertNotNull($agent->fresh()->two_factor_confirmed_at);
    }

    public function test_developer_mode_keeps_two_factor_optional(): void
    {
        $this->app['env'] = 'production';
        config(['custovis.dev_mode' => true]);

        $this->actingAs($this->agent())->get('/agent')->assertOk();
    }

    public function test_gdpr_export_and_anonymisation_via_admin_ui(): void
    {
        Storage::fake('local');
        $customer = Customer::factory()->create(['name' => 'Erika Muster', 'email' => 'erika@example.com']);
        $ticket = $this->ticket(['customer_id' => $customer->id, 'requester_email' => 'erika@example.com', 'requester_name' => 'Erika Muster']);
        $ticket->messages()->create(['visibility' => 'public', 'direction' => 'incoming', 'body_text' => 'Meine IBAN lautet DE00']);
        $admin = User::factory()->create();
        $admin->assignRole('system_admin');

        $export = Livewire::actingAs($admin)->test(ComplianceCenter::class)->set('identifier', 'erika@example.com')->call('export');
        $export->assertFileDownloaded();
        $this->assertSame([], Storage::disk('local')->allFiles('gdpr-exports'), 'export file must be removed after download');

        Livewire::actingAs($admin)->test(ComplianceCenter::class)
            ->set('identifier', 'erika@example.com')->set('confirmation', 'erika@example.com')
            ->call('anonymize')->assertSet('result', '1 ticket(s) anonymised.');

        $this->assertNull($ticket->fresh()->requester_email);
        $this->assertSame('[anonymisiert]', $ticket->messages()->first()->body_text);
        $this->assertStringEndsWith('@invalid.invalid', $customer->fresh()->email);
        $log = AuditLog::query()->where('action', 'gdpr.anonymized')->firstOrFail();
        $this->assertStringNotContainsString('erika', json_encode($log->meta));
    }

    public function test_gdpr_export_command_contains_the_subjects_tickets(): void
    {
        Storage::fake('local');
        $this->ticket(['requester_phone' => '+4930999', 'subject' => 'Anruf wegen Rechnung']);

        $this->artisan('gdpr:export-customer-data', ['identifier' => '+4930999'])->assertSuccessful();

        $file = Storage::disk('local')->allFiles('gdpr-exports')[0];
        $this->assertStringContainsString('Anruf wegen Rechnung', Storage::disk('local')->get($file));
    }

    public function test_compliance_center_requires_permission(): void
    {
        Livewire::actingAs($this->agent())->test(ComplianceCenter::class)->assertForbidden();
    }

    public function test_security_relevant_changes_are_audited_without_secret_values(): void
    {
        $ticket = $this->ticket();
        $ticket->update(['status' => 'closed', 'subject' => 'nicht auditiert']);
        $mailbox = Mailbox::query()->create([
            'team_id' => $this->team->id, 'name' => 'Support', 'email_address' => 'support@example.com',
            'imap_host' => 'imap.example.com', 'imap_port' => 993, 'imap_username' => 'u', 'imap_password' => 'alt-geheim',
            'smtp_host' => 'smtp.example.com', 'smtp_port' => 587, 'smtp_username' => 'u', 'smtp_password' => 'x',
        ]);
        $mailbox->update(['imap_password' => 'neu-geheim']);

        $ticketLog = AuditLog::query()->where('action', 'ticket.updated')->firstOrFail();
        $this->assertSame(['status' => ['from' => 'open', 'to' => 'closed']], $ticketLog->meta['changes']);
        $mailboxLog = AuditLog::query()->where('action', 'mailbox.updated')->firstOrFail();
        $this->assertSame(['imap_password'], $mailboxLog->meta['secrets_changed']);
        $this->assertStringNotContainsString('geheim', AuditLog::query()->get()->toJson());
    }

    public function test_retention_policies_delete_old_audit_logs_and_anonymise_old_closed_tickets(): void
    {
        $old = $this->ticket(['status' => 'closed', 'closed_at' => now()->subDays(400), 'requester_email' => 'alt@example.com']);
        $recent = $this->ticket(['status' => 'closed', 'closed_at' => now()->subDays(5), 'requester_email' => 'neu@example.com']);
        AuditLog::record('old.entry', null, null)->forceFill(['created_at' => now()->subDays(800)])->save();
        Setting::write('retention.audit_log_days', '730');
        Setting::write('retention.closed_ticket_days', '365');

        $this->artisan('retention:apply')->assertSuccessful();

        $this->assertDatabaseMissing('audit_logs', ['action' => 'old.entry']);
        $this->assertNull($old->fresh()->requester_email);
        $this->assertSame('neu@example.com', $recent->fresh()->requester_email);
    }

    private function agent(): User
    {
        $user = User::factory()->create();
        $user->assignRole('agent');
        $this->team->users()->attach($user);

        return $user;
    }

    private function ticket(array $attributes = []): Ticket
    {
        return Ticket::query()->create($attributes + ['team_id' => $this->team->id, 'source' => 'api', 'subject' => 'Ticket']);
    }
}
