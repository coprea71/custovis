<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\MailboxManager;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
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
}
