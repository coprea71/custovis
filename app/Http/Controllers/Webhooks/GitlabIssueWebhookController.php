<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Models\GitIssueConnection;
use App\Services\GitIssueImportService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class GitlabIssueWebhookController extends Controller
{
    public function __invoke(Request $request, GitIssueConnection $connection, GitIssueImportService $importer): Response
    {
        if ($connection->provider !== 'gitlab' || $connection->isRevoked()) {
            abort(404);
        }

        $this->verifyToken($request, $connection);

        $payload = $request->json()->all();
        $kind = $payload['object_kind'] ?? null;

        if ($kind === 'issue') {
            $issue = $payload['object_attributes'];

            $importer->importIssue(
                connection: $connection,
                externalIssueId: (string) $issue['iid'],
                title: $issue['title'],
                body: $issue['description'],
                authorName: $payload['user']['username'] ?? 'unknown',
                status: $issue['state'] === 'closed' ? 'closed' : 'open',
                labels: array_map(fn ($label) => $label['title'], $payload['labels'] ?? []),
            );
        } elseif ($kind === 'note' && ($payload['object_attributes']['noteable_type'] ?? null) === 'Issue') {
            $note = $payload['object_attributes'];

            $importer->importComment(
                connection: $connection,
                externalIssueId: (string) ($payload['issue']['iid'] ?? ''),
                externalCommentId: (string) $note['id'],
                body: $note['note'],
                authorName: $payload['user']['username'] ?? 'unknown',
            );
        }

        return response()->noContent();
    }

    private function verifyToken(Request $request, GitIssueConnection $connection): void
    {
        $token = (string) $request->header('X-Gitlab-Token', '');

        abort_unless(
            hash_equals((string) $connection->webhook_secret, $token),
            403,
            'Invalid webhook token.'
        );
    }
}
