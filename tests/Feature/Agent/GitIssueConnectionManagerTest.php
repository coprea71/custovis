<?php

namespace Tests\Feature\Agent;

use App\Livewire\Agent\Team\GitIssueConnectionManager;
use App\Models\GitIssueConnection;
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

    public function test_webhook_url_is_shown_for_active_webhook_connections(): void
    {
        $team = Team::query()->create(['name' => 'Support', 'slug' => 'support']);
        $user = User::factory()->create();
        $team->users()->attach($user, ['role_in_team' => 'team_admin']);

        $connection = GitIssueConnection::query()->create([
            'team_id' => $team->id,
            'provider' => 'github',
            'repository' => 'acme/widgets',
            'access_token' => 'ghp_secret',
            'webhook_secret' => 'wh_secret_123',
            'sync_mode' => 'webhook',
            'created_by' => $user->id,
        ]);

        Livewire::actingAs($user)
            ->test(GitIssueConnectionManager::class, ['team' => $team])
            ->assertSee(url("/api/v1/git-issues/github/{$connection->id}"))
            ->assertDontSee('wh_secret_123');
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

    public function test_gitlab_connection_stores_self_hosted_base_url(): void
    {
        [$team, $user] = $this->teamWithAdmin();

        Livewire::actingAs($user)
            ->test(GitIssueConnectionManager::class, ['team' => $team])
            ->set('provider', 'gitlab')
            ->set('repository', 'acme/widgets')
            ->set('baseUrl', 'https://gitlab.example.de')
            ->set('accessToken', 'glpat_secret')
            ->set('webhookSecret', 'wh_secret_123')
            ->set('syncMode', 'poll')
            ->call('createConnection')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('git_issue_connections', [
            'team_id' => $team->id,
            'provider' => 'gitlab',
            'base_url' => 'https://gitlab.example.de',
        ]);
    }

    public function test_gitlab_base_url_rejects_credentials_in_url(): void
    {
        [$team, $user] = $this->teamWithAdmin();

        Livewire::actingAs($user)
            ->test(GitIssueConnectionManager::class, ['team' => $team])
            ->set('provider', 'gitlab')
            ->set('repository', 'acme/widgets')
            ->set('baseUrl', 'https://user:pass@gitlab.example.de')
            ->set('accessToken', 'glpat_secret')
            ->set('webhookSecret', 'wh_secret_123')
            ->call('createConnection')
            ->assertHasErrors('baseUrl');

        $this->assertDatabaseCount('git_issue_connections', 0);
    }

    public function test_base_url_is_ignored_for_github(): void
    {
        [$team, $user] = $this->teamWithAdmin();

        Livewire::actingAs($user)
            ->test(GitIssueConnectionManager::class, ['team' => $team])
            ->set('provider', 'github')
            ->set('repository', 'acme/widgets')
            ->set('baseUrl', 'not a url')
            ->set('accessToken', 'ghp_secret')
            ->set('webhookSecret', 'wh_secret_123')
            ->call('createConnection')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('git_issue_connections', ['provider' => 'github', 'base_url' => null]);
    }

    /**
     * @return array{Team, User}
     */
    private function teamWithAdmin(): array
    {
        $team = Team::query()->create(['name' => 'Support', 'slug' => 'support']);
        $user = User::factory()->create();
        $team->users()->attach($user, ['role_in_team' => 'team_admin']);

        return [$team, $user];
    }
}
