<?php

namespace Tests\Feature\Portal;

use App\Http\Controllers\Portal\PortalPasswordResetController;
use App\Livewire\Admin\CustomerManager;
use App\Mail\CustomerPasswordLinkMail;
use App\Models\AuditLog;
use App\Models\Customer;
use App\Models\Team;
use App\Models\Ticket;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

class CustomerManagementTest extends TestCase
{
    use RefreshDatabase;

    private Team $team;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->team = Team::query()->create(['name' => 'Support', 'slug' => 'support']);
        $this->admin = User::factory()->create();
        $this->admin->assignRole('system_admin');
    }

    public function test_admin_creates_customer_and_existing_tickets_are_linked(): void
    {
        $matching = $this->ticket(['requester_email' => 'erika@example.com']);
        $foreign = $this->ticket(['requester_email' => 'other@example.com']);

        Livewire::actingAs($this->admin)->test(CustomerManager::class)
            ->set('name', 'Erika Muster')->set('email', 'Erika@Example.com')
            ->call('save')->assertHasNoErrors();

        $customer = Customer::query()->where('email', 'erika@example.com')->firstOrFail();
        $this->assertTrue($customer->active);
        $this->assertSame($customer->id, $matching->fresh()->customer_id);
        $this->assertNull($foreign->fresh()->customer_id);

        $log = AuditLog::query()->where('action', 'customer.created')->firstOrFail();
        $this->assertSame(['tickets_linked' => 1], $log->meta);
        $this->assertStringNotContainsString('erika', AuditLog::query()->get()->toJson());
    }

    public function test_email_must_be_unique_and_admin_can_edit_customer(): void
    {
        $customer = Customer::factory()->create(['name' => 'Alt', 'email' => 'kunde@example.com']);
        Customer::factory()->create(['email' => 'belegt@example.com']);

        Livewire::actingAs($this->admin)->test(CustomerManager::class)
            ->set('name', 'Doppelt')->set('email', 'belegt@example.com')
            ->call('save')->assertHasErrors(['email' => 'unique'])
            ->call('edit', $customer->id)->set('name', 'Neu')
            ->call('save')->assertHasNoErrors();

        $this->assertSame('Neu', $customer->fresh()->name);
        $this->assertSame(['fields_changed' => ['name']], AuditLog::query()->where('action', 'customer.updated')->firstOrFail()->meta);
    }

    public function test_invited_customer_sets_password_and_can_log_in(): void
    {
        Mail::fake();
        $customer = Customer::factory()->create(['email' => 'kunde@example.com']);

        Livewire::actingAs($this->admin)->test(CustomerManager::class)->call('invite', $customer->id);

        $url = null;
        Mail::assertSent(CustomerPasswordLinkMail::class, function (CustomerPasswordLinkMail $mail) use (&$url) {
            $url = $mail->url();

            return $mail->invitation && $mail->hasTo('kunde@example.com');
        });
        $this->assertStringContainsString('/portal/reset-password/', $url);
        $this->assertStringContainsString('Kundenportal', (new CustomerPasswordLinkMail($customer, 'token', true))->render());
        $this->assertDatabaseHas('audit_logs', ['action' => 'customer.invited', 'auditable_id' => $customer->id]);

        $token = basename(parse_url($url, PHP_URL_PATH));
        $this->get($url)->assertOk()->assertSee('Passwort festlegen');
        $this->post('/portal/reset-password', [
            'token' => $token, 'email' => 'kunde@example.com',
            'password' => 'ein-sicheres-passwort', 'password_confirmation' => 'ein-sicheres-passwort',
        ])->assertRedirect('/portal/login');

        $this->post('/portal/login', ['email' => 'kunde@example.com', 'password' => 'ein-sicheres-passwort'])->assertRedirect('/portal');
        $this->assertAuthenticatedAs($customer, 'customer');
    }

    public function test_reset_rejects_short_passwords_and_invalid_tokens(): void
    {
        Customer::factory()->create(['email' => 'kunde@example.com']);

        $this->post('/portal/reset-password', [
            'token' => 'x', 'email' => 'kunde@example.com', 'password' => 'kurz', 'password_confirmation' => 'kurz',
        ])->assertSessionHasErrors('password');

        $this->post('/portal/reset-password', [
            'token' => 'falsch', 'email' => 'kunde@example.com',
            'password' => 'ein-sicheres-passwort', 'password_confirmation' => 'ein-sicheres-passwort',
        ])->assertSessionHasErrors('email');
    }

    public function test_locked_customer_cannot_log_in_and_active_session_ends(): void
    {
        $customer = Customer::factory()->create(['email' => 'kunde@example.com']);

        Livewire::actingAs($this->admin)->test(CustomerManager::class)->call('toggleActive', $customer->id);
        $this->assertFalse($customer->fresh()->active);
        $this->assertDatabaseHas('audit_logs', ['action' => 'customer.locked', 'auditable_id' => $customer->id]);

        $this->actingAs($customer->fresh(), 'customer')->get('/portal')->assertRedirect('/portal/login');
        $this->assertGuest('customer');

        $this->post('/portal/login', ['email' => 'kunde@example.com', 'password' => 'password'])
            ->assertSessionHasErrors('email');
        $this->assertGuest('customer');
    }

    public function test_forgot_password_answers_identically_for_known_unknown_and_locked_addresses(): void
    {
        Mail::fake();
        Customer::factory()->create(['email' => 'kunde@example.com']);
        Customer::factory()->create(['email' => 'gesperrt@example.com', 'active' => false]);

        foreach (['kunde@example.com', 'unbekannt@example.com', 'gesperrt@example.com'] as $email) {
            $this->from('/portal/forgot-password')->post('/portal/forgot-password', ['email' => $email])
                ->assertRedirect('/portal/forgot-password')
                ->assertSessionHas('status', PortalPasswordResetController::NEUTRAL_STATUS)
                ->assertSessionHasNoErrors();
        }

        Mail::assertSent(CustomerPasswordLinkMail::class, 1);
        Mail::assertSent(CustomerPasswordLinkMail::class, fn (CustomerPasswordLinkMail $mail) => $mail->hasTo('kunde@example.com') && ! $mail->invitation);
    }

    public function test_forgot_password_is_rate_limited(): void
    {
        Mail::fake();

        foreach (range(1, 5) as $attempt) {
            $this->post('/portal/forgot-password', ['email' => 'x@example.com'])->assertSessionHasNoErrors();
        }

        $this->post('/portal/forgot-password', ['email' => 'x@example.com'])->assertSessionHasErrors('email');
    }

    public function test_login_page_links_to_forgot_password(): void
    {
        $this->get('/portal/login')->assertOk()->assertSee('Passwort vergessen?');
        $this->get('/portal/forgot-password')->assertOk();
    }

    public function test_customer_management_requires_permission(): void
    {
        $agent = User::factory()->create();
        $agent->assignRole('agent');
        $customer = Customer::factory()->create();

        Livewire::actingAs($agent)->test(CustomerManager::class)->assertForbidden();
        $this->actingAs($agent)->get('/admin/customers')->assertForbidden();
        $this->actingAs($this->admin)->get('/admin/customers')->assertOk()->assertSee($customer->name);
    }

    private function ticket(array $attributes = []): Ticket
    {
        return Ticket::query()->create($attributes + ['team_id' => $this->team->id, 'source' => 'api', 'subject' => 'Ticket']);
    }
}
