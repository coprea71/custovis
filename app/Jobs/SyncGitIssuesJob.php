<?php

namespace App\Jobs;

use App\Models\GitIssueConnection;
use App\Services\GitIssueImportService;
use App\Support\PublicHost;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class SyncGitIssuesJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public GitIssueConnection $gitConnection)
    {
        $this->onQueue('git-sync');
    }

    public function handle(GitIssueImportService $importer): void
    {
        if ($this->gitConnection->isRevoked() || $this->gitConnection->sync_mode !== GitIssueConnection::SYNC_POLL) {
            return;
        }

        try {
            match ($this->gitConnection->provider) {
                GitIssueConnection::PROVIDER_GITHUB => $this->syncGithub($importer),
                GitIssueConnection::PROVIDER_GITLAB => $this->syncGitlab($importer),
                default => null,
            };

            $this->gitConnection->update(['last_synced_at' => now()]);
        } catch (Throwable $e) {
            Log::error("SyncGitIssuesJob: failed for connection [{$this->gitConnection->id}]: {$e->getMessage()}");
        }
    }

    private function syncGithub(GitIssueImportService $importer): void
    {
        $since = $this->gitConnection->last_synced_at?->toIso8601String();

        $response = Http::withToken($this->gitConnection->access_token)
            ->get("https://api.github.com/repos/{$this->gitConnection->repository}/issues", array_filter([
                'state' => 'all',
                'since' => $since,
            ]))
            ->throw();

        foreach ($response->json() ?? [] as $issue) {
            if (isset($issue['pull_request'])) {
                continue; // GitHub's issues endpoint also returns PRs.
            }

            $importer->importIssue(
                connection: $this->gitConnection,
                externalIssueId: (string) $issue['number'],
                title: $issue['title'],
                body: $issue['body'],
                authorName: $issue['user']['login'] ?? 'unknown',
                status: $issue['state'] === 'closed' ? 'closed' : 'open',
                labels: array_map(fn ($label) => $label['name'], $issue['labels'] ?? []),
            );

            $this->syncGithubComments($importer, (string) $issue['number']);
        }
    }

    private function syncGithubComments(GitIssueImportService $importer, string $issueNumber): void
    {
        $response = Http::withToken($this->gitConnection->access_token)
            ->get("https://api.github.com/repos/{$this->gitConnection->repository}/issues/{$issueNumber}/comments")
            ->throw();

        foreach ($response->json() ?? [] as $comment) {
            $importer->importComment(
                connection: $this->gitConnection,
                externalIssueId: $issueNumber,
                externalCommentId: (string) $comment['id'],
                body: $comment['body'],
                authorName: $comment['user']['login'] ?? 'unknown',
            );
        }
    }

    private function syncGitlab(GitIssueImportService $importer): void
    {
        $projectPath = urlencode($this->gitConnection->repository);
        $since = $this->gitConnection->last_synced_at?->toIso8601String();

        $response = $this->gitlabRequest()
            ->get("{$this->gitConnection->gitlabApiUrl()}/projects/{$projectPath}/issues", array_filter([
                'updated_after' => $since,
            ]))
            ->throw();

        foreach ($response->json() ?? [] as $issue) {
            $importer->importIssue(
                connection: $this->gitConnection,
                externalIssueId: (string) $issue['iid'],
                title: $issue['title'],
                body: $issue['description'],
                authorName: $issue['author']['username'] ?? 'unknown',
                status: $issue['state'] === 'closed' ? 'closed' : 'open',
                labels: $issue['labels'] ?? [],
            );

            $this->syncGitlabComments($importer, $projectPath, (string) $issue['iid']);
        }
    }

    private function syncGitlabComments(GitIssueImportService $importer, string $projectPath, string $issueIid): void
    {
        $response = $this->gitlabRequest()
            ->get("{$this->gitConnection->gitlabApiUrl()}/projects/{$projectPath}/issues/{$issueIid}/notes")
            ->throw();

        foreach ($response->json() ?? [] as $note) {
            if ($note['system'] ?? false) {
                continue;
            }

            $importer->importComment(
                connection: $this->gitConnection,
                externalIssueId: $issueIid,
                externalCommentId: (string) $note['id'],
                body: $note['body'],
                authorName: $note['author']['username'] ?? 'unknown',
            );
        }
    }

    private function gitlabRequest(): PendingRequest
    {
        return PublicHost::guard(
            Http::withHeaders(['PRIVATE-TOKEN' => $this->gitConnection->access_token]),
            fn () => new RuntimeException('GitLab host resolves to an internal address.'),
        );
    }
}
