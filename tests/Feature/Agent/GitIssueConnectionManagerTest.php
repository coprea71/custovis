<?php

namespace Tests\Feature\Agent;

use App\Livewire\Agent\Team\GitIssueConnectionManager;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class GitIssueConnectionManagerTest extends TestCase
{
    use RefreshDatabase;

    public function test_team_admin_can_create_a_connection_for_own_team(): void
    {
        $team = Team::query()->create(['name' => 'Support', 'slug' => 'support']);
        $user = User::factory()->create();
        $team->users()->attach($user, ['role_in_team' => 'team_admin']);

        Livewire::actingAs($user)
            ->test(GitIssueConnectionManager::class, ['team' => $team])
            ->set('provider', 'github')
            ->set('repository', 'acme/widgets')
            ->set('accessToken', 'ghp_secret')
            ->set('webhookSecret', 'wh_secret_123')
            ->set('syncMode', 'webhook')
            ->call('createConnection');

        $this->assertDatabaseHas('git_issue_connections', [
            'team_id' => $team->id,
            'repository' => 'acme/widgets',
        ]);
    }

    public function test_plain_member_cannot_manage_git_connections(): void
    {
        $team = Team::query()->create(['name' => 'Support', 'slug' => 'support']);
        $user = User::factory()->create();
        $team->users()->attach($user, ['role_in_team' => 'member']);

        Livewire::actingAs($user)
            ->test(GitIssueConnectionManager::class, ['team' => $team])
            ->assertForbidden();
    }
}
