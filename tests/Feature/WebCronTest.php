<?php

namespace Tests\Feature;

use App\Livewire\Admin\SystemMaintenance;
use App\Models\Mailbox;
use App\Models\Setting;
use App\Models\User;
use App\Services\MailboxImapService;
use App\Services\WebCronService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Mockery\MockInterface;
use RuntimeException;
use Tests\TestCase;

class WebCronTest extends TestCase
{
    use RefreshDatabase;

    public function test_unknown_or_unconfigured_token_is_not_found(): void
    {
        $this->get('/cron/irgendwas')->assertNotFound();

        app(WebCronService::class)->regenerateToken();

        $this->get('/cron/irgendwas')->assertNotFound();
        $this->assertNull(Setting::read(WebCronService::LAST_RUN_KEY));
    }

    public function test_valid_token_runs_due_schedule_and_processes_the_queue(): void
    {
        // A real queue proves the queued fetch job is worked off within the same call.
        config(['queue.default' => 'database']);
        $mailbox = Mailbox::factory()->create();
        $this->mock(MailboxImapService::class, fn (MockInterface $mock) => $mock
            ->shouldReceive('connect')->andThrow(new RuntimeException('AUTHENTICATIONFAILED')));
        $token = app(WebCronService::class)->regenerateToken();

        $this->get('/cron/'.$token)->assertOk()->assertSee('OK');

        $this->assertSame('AUTHENTICATIONFAILED', $mailbox->fresh()->last_fetch_error);
        $this->assertDatabaseCount('jobs', 0);
        $this->assertNotNull(Setting::read(WebCronService::LAST_RUN_KEY));
    }

    public function test_admin_generates_url_once_and_only_its_hash_is_stored(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $admin = User::factory()->create();
        $admin->assignRole('system_admin');

        $component = Livewire::actingAs($admin)
            ->test(SystemMaintenance::class)
            ->assertSee('Noch keine Web-Cron-URL erzeugt.')
            ->call('regenerateCronUrl');

        $token = basename((string) $component->get('cronUrl'));
        $this->assertTrue(app(WebCronService::class)->verify($token));
        $this->assertDatabaseMissing('settings', ['value' => $token]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'system.web_cron_token_regenerated', 'user_id' => $admin->id]);
    }
}
