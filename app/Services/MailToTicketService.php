<?php

namespace App\Services;

use App\DataTransferObjects\IncomingMailMessageData;
use App\Models\Mailbox;
use App\Models\Ticket;
use App\Models\TicketMessage;

/**
 * Maps a parsed incoming mail into a ticket/ticket_message, matching
 * replies to their existing ticket via In-Reply-To/References headers
 * instead of subject-line parsing (subjects get mangled by mail clients,
 * headers don't).
 */
class MailToTicketService
{
    public function __construct(private readonly AttachmentService $attachments) {}

    public function import(Mailbox $mailbox, IncomingMailMessageData $data): TicketMessage
    {
        if (TicketMessage::query()->where('message_id', $data->messageId)->exists()) {
            return TicketMessage::query()->where('message_id', $data->messageId)->firstOrFail();
        }

        $ticket = $this->findExistingTicket($mailbox, $data) ?? $this->createTicket($mailbox, $data);
        $this->raisePriority($ticket, $data->priority);

        $message = $ticket->messages()->create([
            'visibility' => TicketMessage::VISIBILITY_PUBLIC,
            'direction' => 'incoming',
            'external_author_name' => $data->fromName,
            'external_author_email' => $data->fromEmail,
            'body_html' => $data->bodyHtml,
            'body_text' => $data->bodyText,
            'message_id' => $data->messageId,
        ]);

        foreach ($data->attachments as $attachment) {
            $this->attachments->storeRawContent($message, $attachment->content, $attachment->name, $attachment->mimeType);
        }

        return $message;
    }

    /**
     * A mail flagged as high/urgent raises the ticket priority, never lowers it.
     */
    private function raisePriority(Ticket $ticket, ?string $priority): void
    {
        $rank = array_flip(Ticket::PRIORITIES);

        if ($priority !== null && isset($rank[$priority]) && $rank[$priority] > ($rank[$ticket->priority] ?? 0)) {
            $ticket->update(['priority' => $priority]);
        }
    }

    private function findExistingTicket(Mailbox $mailbox, IncomingMailMessageData $data): ?Ticket
    {
        $referencedIds = array_filter([$data->inReplyTo, ...$data->references]);

        if ($referencedIds === []) {
            return null;
        }

        return Ticket::query()
            ->where('mailbox_id', $mailbox->id)
            ->whereHas('messages', fn ($query) => $query->whereIn('message_id', $referencedIds))
            ->first();
    }

    private function createTicket(Mailbox $mailbox, IncomingMailMessageData $data): Ticket
    {
        return Ticket::query()->create([
            'team_id' => $mailbox->team_id,
            'mailbox_id' => $mailbox->id,
            'type' => 'support_ticket',
            'source' => 'mailbox',
            'subject' => $data->subject,
            'requester_email' => $data->fromEmail,
            'requester_name' => $data->fromName,
            // Set on creation so the SLA policy of the right priority applies.
            'priority' => $data->priority ?? 'normal',
        ]);
    }
}
