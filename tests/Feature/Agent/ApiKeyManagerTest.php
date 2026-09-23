<?php

namespace Tests\Feature\Agent;

use App\Livewire\Agent\Team\ApiKeyManager;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ApiKeyManagerTest extends TestCase
{
    use RefreshDatabase;

    public function test_team_admin_can_create_and_revoke_a_key_for_own_team(): void
    {
        $team = Team::query()->create(['name' => 'Support', 'slug' => 'support']);
        $user = User::factory()->create();
        $team->users()->attach($user, ['role_in_team' => 'team_admin']);

        Livewire::actingAs($user)
            ->test(ApiKeyManager::class, ['team' => $team])
            ->set('newClientName', 'Webshop')
            ->call('createClient')
            ->assertSet('readOnly', false);

        $this->assertDatabaseHas('api_clients', ['team_id' => $team->id, 'name' => 'Webshop']);

        $clientId = $team->apiClients()->first()->id;

        Livewire::actingAs($user)
            ->test(ApiKeyManager::class, ['team' => $team])
            ->call('revokeClient', $clientId);

        $this->assertNotNull($team->apiClients()->first()->refresh()->revoked_at);
    }

    public function test_plain_member_cannot_manage_api_keys(): void
    {
        $team = Team::query()->create(['name' => 'Support', 'slug' => 'support']);
        $user = User::factory()->create();
        $team->users()->attach($user, ['role_in_team' => 'member']);

        Livewire::actingAs($user)
            ->test(ApiKeyManager::class, ['team' => $team])
            ->assertForbidden();
    }

    public function test_system_admin_sees_read_only_view_for_a_team_they_do_not_belong_to(): void
    {
        Permission::findOrCreate('team.api_keys.manage', 'web');
        Role::findOrCreate('system_admin', 'web')->givePermissionTo('team.api_keys.manage');

        $team = Team::query()->create(['name' => 'Support', 'slug' => 'support']);
        $admin = User::factory()->create();
        $admin->assignRole('system_admin');

        Livewire::actingAs($admin)
            ->test(ApiKeyManager::class, ['team' => $team])
            ->assertSet('readOnly', true)
            ->set('newClientName', 'Should not be allowed')
            ->call('createClient')
            ->assertForbidden();
    }
}
