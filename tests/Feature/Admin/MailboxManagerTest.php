<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\MailboxManager;
use App\Models\Mailbox;
use App\Models\Team;
use App\Models\User;
use App\Services\MailboxImapService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Mockery\MockInterface;
use RuntimeException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class MailboxManagerTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_without_permission_is_forbidden(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(MailboxManager::class)
            ->assertForbidden();
    }

    public function test_system_admin_can_create_a_mailbox(): void
    {
        Permission::findOrCreate('mailboxes.manage', 'web');
        $role = Role::findOrCreate('system_admin', 'web');
        $role->givePermissionTo('mailboxes.manage');

        $user = User::factory()->create();
        $user->assignRole('system_admin');

        $team = Team::query()->create(['name' => 'Support', 'slug' => 'support']);

        Livewire::actingAs($user)
            ->test(MailboxManager::class)
            ->set('team_id', $team->id)
            ->set('name', 'Support Inbox')
            ->set('email_address', 'support@example.com')
            ->set('imap_host', 'imap.example.com')
            ->set('imap_username', 'support@example.com')
            ->set('imap_password', 'secret')
            ->set('smtp_host', 'smtp.example.com')
            ->set('smtp_username', 'support@example.com')
            ->set('smtp_password', 'secret')
            ->call('save');

        $this->assertDatabaseHas('mailboxes', [
            'team_id' => $team->id,
            'email_address' => 'support@example.com',
        ]);
    }

    public function test_connection_test_reports_unseen_count(): void
    {
        $mailbox = Mailbox::factory()->create();
        $this->mock(MailboxImapService::class, fn (MockInterface $mock) => $mock
            ->shouldReceive('countUnseen')->once()->andReturn(3));

        Livewire::actingAs($this->createAdmin())
            ->test(MailboxManager::class)
            ->call('testConnection', $mailbox->id)
            ->assertSet("testResults.{$mailbox->id}.ok", true)
            ->assertSee('3 ungelesene Nachricht(en)');
    }

    public function test_connection_test_reports_failure(): void
    {
        $mailbox = Mailbox::factory()->create();
        $this->mock(MailboxImapService::class, fn (MockInterface $mock) => $mock
            ->shouldReceive('countUnseen')->once()->andThrow(new RuntimeException('Login failed')));

        Livewire::actingAs($this->createAdmin())
            ->test(MailboxManager::class)
            ->call('testConnection', $mailbox->id)
            ->assertSet("testResults.{$mailbox->id}.ok", false)
            ->assertSee('Verbindung fehlgeschlagen: Login failed');
    }

    public function test_mailbox_with_fetch_error_shows_warning(): void
    {
        Mailbox::factory()->withFetchError('AUTHENTICATIONFAILED')->create();

        Livewire::actingAs($this->createAdmin())
            ->test(MailboxManager::class)
            ->assertSee('Abruffehler seit')
            ->assertSee('AUTHENTICATIONFAILED');
    }

    private function createAdmin(): User
    {
        Permission::findOrCreate('mailboxes.manage', 'web');
        Role::findOrCreate('system_admin', 'web')->givePermissionTo('mailboxes.manage');

        $user = User::factory()->create();
        $user->assignRole('system_admin');

        return $user;
    }
}
