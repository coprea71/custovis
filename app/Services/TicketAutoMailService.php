<?php

namespace App\Services;

use App\Jobs\SendTicketReplyJob;
use App\Models\TeamMailLayout;
use App\Models\Ticket;
use App\Models\TicketMessage;
use Illuminate\Support\Str;

/**
 * Automatic customer mails of tickets that reach their customer by e-mail
 * (mailbox and API tickets with a sending mailbox). They are stored as ticket
 * messages, so agents see in the history what the customer received and a
 * customer reply threads into the ticket via the Message-ID.
 */
class TicketAutoMailService
{
    /**
     * Never acknowledge bounces or other robots: an auto-reply to an
     * auto-reply is how mail loops start.
     */
    private const AUTOMATED_SENDER = '/^(mailer-daemon|postmaster|no-?reply|do-?not-?reply)@/i';

    public function acknowledge(Ticket $ticket): void
    {
        $sender = Str::lower((string) $ticket->requester_email);

        if (preg_match(self::AUTOMATED_SENDER, $sender) || $sender === Str::lower((string) $ticket->mailbox?->email_address)
            || $ticket->customer?->notify_acknowledgement === false) {
            return;
        }

        $this->send($ticket, 'acknowledgement');
    }

    /**
     * A closed ticket gets the "resolved" mail; if the team or customer switched
     * that off, the customer's status-change subscription still informs them.
     */
    public function statusChanged(Ticket $ticket): void
    {
        if ($ticket->auto_mails_muted) {
            return;
        }

        if ($ticket->status === 'closed' && $ticket->customer?->notify_resolved !== false && $this->send($ticket, 'resolved')) {
            return;
        }

        if ($ticket->customer?->notify_status_changes) {
            $this->send($ticket, 'status_change');
        }
    }

    private function send(Ticket $ticket, string $type): bool
    {
        if (! $ticket->sendsMail() || $ticket->spam_at || ! $ticket->requester_email) {
            return false;
        }

        $layout = TeamMailLayout::forTeam($ticket->team);

        if (! $layout->autoMailEnabled($type)) {
            return false;
        }

        // The triggering agent counts as author, so their own status change
        // does not mark the ticket unread for them; null for system triggers.
        $agent = auth('web')->user();
        $body = strtr($layout->autoMailText($type), TeamMailLayout::placeholderValues($ticket, $agent));

        $message = $ticket->messages()->create([
            'visibility' => TicketMessage::VISIBILITY_PUBLIC,
            'direction' => 'outgoing',
            'automatic' => true,
            'author_user_id' => $agent?->id,
            'body_html' => nl2br(e($body)),
            'body_text' => $body,
            'message_id' => TicketMessage::newMessageId(),
        ]);

        SendTicketReplyJob::dispatch($message)->afterCommit();

        return true;
    }
}
