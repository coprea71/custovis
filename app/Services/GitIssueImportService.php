<?php

namespace App\Services;

use App\Models\GitIssueConnection;
use App\Models\Ticket;
use App\Models\TicketMessage;

/**
 * Maps GitHub/GitLab issues and comments into tickets/ticket_messages,
 * shared by both the webhook path and the polling fallback (3.md) so the
 * mapping logic lives in exactly one place.
 */
class GitIssueImportService
{
    public function importIssue(
        GitIssueConnection $connection,
        string $externalIssueId,
        string $title,
        ?string $body,
        string $authorName,
        string $status,
        array $labels = [],
    ): Ticket {
        $externalRef = $this->issueRef($connection, $externalIssueId);

        $ticket = Ticket::query()->where('source', 'git_issue')->where('external_ref', $externalRef)->first();

        if ($ticket) {
            $ticket->update(['status' => $status, 'tags' => $labels]);

            return $ticket;
        }

        $ticket = Ticket::query()->create([
            'team_id' => $connection->team_id,
            'type' => 'support_ticket',
            'source' => 'git_issue',
            'external_ref' => $externalRef,
            'subject' => $title,
            'status' => $status,
            'tags' => $labels,
        ]);

        $ticket->messages()->create([
            'visibility' => TicketMessage::VISIBILITY_PUBLIC,
            'direction' => 'incoming',
            'external_author_name' => $authorName,
            'body_text' => $body,
            'body_html' => null,
            'message_id' => $externalRef,
        ]);

        return $ticket;
    }

    public function importComment(
        GitIssueConnection $connection,
        string $externalIssueId,
        string $externalCommentId,
        string $body,
        string $authorName,
    ): ?TicketMessage {
        $ticket = Ticket::query()
            ->where('source', 'git_issue')
            ->where('external_ref', $this->issueRef($connection, $externalIssueId))
            ->first();

        if (! $ticket) {
            return null;
        }

        $messageId = $this->issueRef($connection, $externalIssueId).':comment:'.$externalCommentId;

        if (TicketMessage::query()->where('message_id', $messageId)->exists()) {
            return TicketMessage::query()->where('message_id', $messageId)->first();
        }

        return $ticket->messages()->create([
            'visibility' => TicketMessage::VISIBILITY_PUBLIC,
            'direction' => 'incoming',
            'external_author_name' => $authorName,
            'body_text' => $body,
            'body_html' => null,
            'message_id' => $messageId,
        ]);
    }

    private function issueRef(GitIssueConnection $connection, string $externalIssueId): string
    {
        return $connection->externalRefPrefix().'#'.$externalIssueId;
    }
}
