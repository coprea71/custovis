<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Models\GitIssueConnection;
use App\Services\GitIssueImportService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class GithubIssueWebhookController extends Controller
{
    public function __invoke(Request $request, GitIssueConnection $connection, GitIssueImportService $importer): Response
    {
        if ($connection->provider !== 'github' || $connection->isRevoked()) {
            abort(404);
        }

        $this->verifySignature($request, $connection);

        $event = $request->header('X-GitHub-Event');
        $payload = $request->json()->all();

        if ($event === 'issues' && in_array($payload['action'] ?? null, ['opened', 'edited', 'closed', 'reopened'], true)) {
            $issue = $payload['issue'];

            $importer->importIssue(
                connection: $connection,
                externalIssueId: (string) $issue['number'],
                title: $issue['title'],
                body: $issue['body'],
                authorName: $issue['user']['login'] ?? 'unknown',
                status: $issue['state'] === 'closed' ? 'closed' : 'open',
                labels: array_map(fn ($label) => $label['name'], $issue['labels'] ?? []),
            );
        } elseif ($event === 'issue_comment' && ($payload['action'] ?? null) === 'created') {
            $importer->importComment(
                connection: $connection,
                externalIssueId: (string) $payload['issue']['number'],
                externalCommentId: (string) $payload['comment']['id'],
                body: $payload['comment']['body'],
                authorName: $payload['comment']['user']['login'] ?? 'unknown',
            );
        }

        return response()->noContent();
    }

    private function verifySignature(Request $request, GitIssueConnection $connection): void
    {
        $signature = $request->header('X-Hub-Signature-256', '');
        $expected = 'sha256='.hash_hmac('sha256', $request->getContent(), $connection->webhook_secret);

        abort_unless(
            hash_equals($expected, $signature),
            403,
            'Invalid webhook signature.'
        );
    }
}
