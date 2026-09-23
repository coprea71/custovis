<?php

namespace Tests\Feature\Api;

use App\Models\GitIssueConnection;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GitIssueWebhookTest extends TestCase
{
    use RefreshDatabase;

    private function makeConnection(string $provider): GitIssueConnection
    {
        $team = Team::query()->create(['name' => 'Support', 'slug' => 'support']);
        $user = User::factory()->create();

        return GitIssueConnection::query()->create([
            'team_id' => $team->id,
            'provider' => $provider,
            'repository' => 'acme/widgets',
            'access_token' => 'token',
            'webhook_secret' => 'my-secret',
            'sync_mode' => 'webhook',
            'created_by' => $user->id,
        ]);
    }

    public function test_github_webhook_with_valid_signature_creates_ticket(): void
    {
        $connection = $this->makeConnection('github');

        $payload = [
            'action' => 'opened',
            'issue' => [
                'number' => 42,
                'title' => 'Button ist kaputt',
                'body' => 'Der Button reagiert nicht.',
                'state' => 'open',
                'user' => ['login' => 'octocat'],
                'labels' => [['name' => 'bug']],
            ],
        ];

        $body = json_encode($payload);
        $signature = 'sha256='.hash_hmac('sha256', $body, 'my-secret');

        $this->call('POST', "/api/v1/git-issues/github/{$connection->id}", [], [], [], [
            'HTTP_X-GitHub-Event' => 'issues',
            'HTTP_X-Hub-Signature-256' => $signature,
            'CONTENT_TYPE' => 'application/json',
        ], $body)->assertNoContent();

        $this->assertDatabaseHas('tickets', [
            'source' => 'git_issue',
            'external_ref' => 'github:acme/widgets#42',
            'subject' => 'Button ist kaputt',
        ]);
    }

    public function test_github_webhook_with_invalid_signature_is_rejected(): void
    {
        $connection = $this->makeConnection('github');

        $body = json_encode(['action' => 'opened', 'issue' => ['number' => 1, 'title' => 'x', 'body' => 'y', 'state' => 'open']]);

        $this->call('POST', "/api/v1/git-issues/github/{$connection->id}", [], [], [], [
            'HTTP_X-GitHub-Event' => 'issues',
            'HTTP_X-Hub-Signature-256' => 'sha256=invalid',
            'CONTENT_TYPE' => 'application/json',
        ], $body)->assertForbidden();

        $this->assertDatabaseMissing('tickets', ['source' => 'git_issue']);
    }

    public function test_repeated_github_webhook_for_same_issue_does_not_duplicate_ticket(): void
    {
        $connection = $this->makeConnection('github');

        $payload = [
            'action' => 'edited',
            'issue' => [
                'number' => 7,
                'title' => 'Login schlägt fehl',
                'body' => 'Details...',
                'state' => 'open',
                'user' => ['login' => 'octocat'],
                'labels' => [],
            ],
        ];
        $body = json_encode($payload);
        $signature = 'sha256='.hash_hmac('sha256', $body, 'my-secret');

        for ($i = 0; $i < 2; $i++) {
            $this->call('POST', "/api/v1/git-issues/github/{$connection->id}", [], [], [], [
                'HTTP_X-GitHub-Event' => 'issues',
                'HTTP_X-Hub-Signature-256' => $signature,
                'CONTENT_TYPE' => 'application/json',
            ], $body)->assertNoContent();
        }

        $this->assertSame(1, \App\Models\Ticket::query()->where('external_ref', 'github:acme/widgets#7')->count());
    }

    public function test_gitlab_webhook_with_valid_token_creates_ticket(): void
    {
        $connection = $this->makeConnection('gitlab');

        $payload = [
            'object_kind' => 'issue',
            'user' => ['username' => 'jdoe'],
            'labels' => [['title' => 'bug']],
            'object_attributes' => [
                'iid' => 5,
                'title' => 'Absturz beim Speichern',
                'description' => 'Details...',
                'state' => 'opened',
            ],
        ];

        $this->postJson("/api/v1/git-issues/gitlab/{$connection->id}", $payload, [
            'X-Gitlab-Token' => 'my-secret',
        ])->assertNoContent();

        $this->assertDatabaseHas('tickets', [
            'source' => 'git_issue',
            'external_ref' => 'gitlab:acme/widgets#5',
            'subject' => 'Absturz beim Speichern',
        ]);
    }

    public function test_gitlab_webhook_with_invalid_token_is_rejected(): void
    {
        $connection = $this->makeConnection('gitlab');

        $this->postJson("/api/v1/git-issues/gitlab/{$connection->id}", [
            'object_kind' => 'issue',
            'object_attributes' => ['iid' => 1, 'title' => 'x', 'description' => 'y', 'state' => 'opened'],
        ], ['X-Gitlab-Token' => 'wrong'])->assertForbidden();
    }
}
