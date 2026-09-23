<?php

namespace App\Jobs;

use App\Models\GitIssueConnection;
use App\Services\GitIssueImportService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class SyncGitIssuesJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public GitIssueConnection $connection)
    {
        $this->onQueue('git-sync');
    }

    public function handle(GitIssueImportService $importer): void
    {
        if ($this->connection->isRevoked() || $this->connection->sync_mode !== GitIssueConnection::SYNC_POLL) {
            return;
        }

        try {
            match ($this->connection->provider) {
                GitIssueConnection::PROVIDER_GITHUB => $this->syncGithub($importer),
                GitIssueConnection::PROVIDER_GITLAB => $this->syncGitlab($importer),
                default => null,
            };

            $this->connection->update(['last_synced_at' => now()]);
        } catch (Throwable $e) {
            Log::error("SyncGitIssuesJob: failed for connection [{$this->connection->id}]: {$e->getMessage()}");
        }
    }

    private function syncGithub(GitIssueImportService $importer): void
    {
        $since = $this->connection->last_synced_at?->toIso8601String();

        $response = Http::withToken($this->connection->access_token)
            ->get("https://api.github.com/repos/{$this->connection->repository}/issues", array_filter([
                'state' => 'all',
                'since' => $since,
            ]))
            ->throw();

        foreach ($response->json() ?? [] as $issue) {
            if (isset($issue['pull_request'])) {
                continue; // GitHub's issues endpoint also returns PRs.
            }

            $importer->importIssue(
                connection: $this->connection,
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
        $response = Http::withToken($this->connection->access_token)
            ->get("https://api.github.com/repos/{$this->connection->repository}/issues/{$issueNumber}/comments")
            ->throw();

        foreach ($response->json() ?? [] as $comment) {
            $importer->importComment(
                connection: $this->connection,
                externalIssueId: $issueNumber,
                externalCommentId: (string) $comment['id'],
                body: $comment['body'],
                authorName: $comment['user']['login'] ?? 'unknown',
            );
        }
    }

    private function syncGitlab(GitIssueImportService $importer): void
    {
        $projectPath = urlencode($this->connection->repository);
        $since = $this->connection->last_synced_at?->toIso8601String();

        $response = Http::withHeaders(['PRIVATE-TOKEN' => $this->connection->access_token])
            ->get("https://gitlab.com/api/v4/projects/{$projectPath}/issues", array_filter([
                'updated_after' => $since,
            ]))
            ->throw();

        foreach ($response->json() ?? [] as $issue) {
            $importer->importIssue(
                connection: $this->connection,
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
        $response = Http::withHeaders(['PRIVATE-TOKEN' => $this->connection->access_token])
            ->get("https://gitlab.com/api/v4/projects/{$projectPath}/issues/{$issueIid}/notes")
            ->throw();

        foreach ($response->json() ?? [] as $note) {
            if ($note['system'] ?? false) {
                continue;
            }

            $importer->importComment(
                connection: $this->connection,
                externalIssueId: $issueIid,
                externalCommentId: (string) $note['id'],
                body: $note['body'],
                authorName: $note['author']['username'] ?? 'unknown',
            );
        }
    }
}
