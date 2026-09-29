<?php

namespace Tests\Feature\Jobs;

use App\Jobs\SyncGitIssuesJob;
use App\Models\GitIssueConnection;
use App\Models\Team;
use App\Models\User;
use App\Services\GitIssueImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SyncGitIssuesJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_gitlab_polling_uses_self_hosted_instance(): void
    {
        Http::fake(['gitlab.example.de/*' => Http::response([])]);

        $this->runJob($this->gitlabConnection('https://gitlab.example.de/'));

        Http::assertSent(fn (Request $request) => str_starts_with($request->url(), 'https://gitlab.example.de/api/v4/projects/acme%2Fwidgets/issues'));
    }

    public function test_gitlab_polling_defaults_to_gitlab_com(): void
    {
        Http::fake(['gitlab.com/*' => Http::response([])]);

        $connection = $this->gitlabConnection(null);
        $this->runJob($connection);

        Http::assertSent(fn (Request $request) => str_starts_with($request->url(), 'https://gitlab.com/api/v4/projects/acme%2Fwidgets/issues'));
        $this->assertNotNull($connection->fresh()->last_synced_at);
    }

    public function test_gitlab_polling_does_not_follow_redirects(): void
    {
        Http::fake([
            'gitlab.example.de/*' => Http::response('', 302, ['Location' => 'https://evil.example.com/steal']),
            'evil.example.com/*' => Http::response([]),
        ]);

        $this->runJob($this->gitlabConnection('https://gitlab.example.de'));

        Http::assertNotSent(fn (Request $request) => str_contains($request->url(), 'evil.example.com'));
    }

    public function test_gitlab_polling_refuses_internal_hosts(): void
    {
        Http::fake();

        $connection = $this->gitlabConnection('https://10.0.0.5');
        $this->runJob($connection);

        Http::assertNothingSent();
        $this->assertNull($connection->fresh()->last_synced_at);
    }

    private function runJob(GitIssueConnection $connection): void
    {
        (new SyncGitIssuesJob($connection))->handle(app(GitIssueImportService::class));
    }

    private function gitlabConnection(?string $baseUrl): GitIssueConnection
    {
        $team = Team::query()->create(['name' => 'Support', 'slug' => 'support']);

        return GitIssueConnection::query()->create([
            'team_id' => $team->id,
            'provider' => GitIssueConnection::PROVIDER_GITLAB,
            'repository' => 'acme/widgets',
            'base_url' => $baseUrl,
            'access_token' => 'glpat_secret',
            'webhook_secret' => 'wh_secret_123',
            'sync_mode' => GitIssueConnection::SYNC_POLL,
            'created_by' => User::factory()->create()->id,
        ]);
    }
}
