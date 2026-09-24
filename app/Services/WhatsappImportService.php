<?php

namespace App\Services;

use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Models\WhatsappAccount;

/**
 * "Ein Chat = ein Ticket": messages from the same phone number on the same
 * WhatsApp account are folded into the existing open ticket, or reopen the
 * most recently closed one, instead of fragmenting the conversation across
 * many tickets (4.md).
 */
class WhatsappImportService
{
    public function __construct(private readonly AttachmentService $attachments) {}

    /**
     * @param  array<int, array{name: string, mimeType: ?string, content: string}>  $mediaAttachments
     */
    public function importInboundMessage(
        WhatsappAccount $account,
        string $waMessageId,
        string $type,
        ?string $text,
        string $fromPhone,
        string $fromName,
        array $mediaAttachments = [],
    ): ?TicketMessage {
        if (TicketMessage::query()->where('whatsapp_message_id', $waMessageId)->exists()) {
            return TicketMessage::query()->where('whatsapp_message_id', $waMessageId)->first();
        }

        $ticket = $this->resolveTicket($account, $fromPhone, $fromName);

        $message = $ticket->messages()->create([
            'visibility' => TicketMessage::VISIBILITY_PUBLIC,
            'direction' => 'incoming',
            'external_author_name' => $fromName,
            'body_text' => $text,
            'message_id' => 'whatsapp-'.$waMessageId,
            'whatsapp_message_id' => $waMessageId,
            'whatsapp_message_type' => $type,
        ]);

        foreach ($mediaAttachments as $media) {
            $this->attachments->storeRawContent($message, $media['content'], $media['name'], $media['mimeType']);
        }

        return $message;
    }

    private function resolveTicket(WhatsappAccount $account, string $fromPhone, string $fromName): Ticket
    {
        $openTicket = Ticket::query()
            ->where('whatsapp_account_id', $account->id)
            ->where('requester_phone', $fromPhone)
            ->whereIn('status', ['open', 'pending', 'reopened'])
            ->latest()
            ->first();

        if ($openTicket) {
            return $openTicket;
        }

        $lastClosedTicket = Ticket::query()
            ->where('whatsapp_account_id', $account->id)
            ->where('requester_phone', $fromPhone)
            ->where('status', 'closed')
            ->latest()
            ->first();

        if ($lastClosedTicket) {
            $lastClosedTicket->update(['status' => 'reopened', 'closed_at' => null]);

            return $lastClosedTicket;
        }

        return Ticket::query()->create([
            'team_id' => $account->team_id,
            'whatsapp_account_id' => $account->id,
            'type' => 'support_ticket',
            'source' => 'whatsapp',
            'subject' => 'WhatsApp-Konversation mit '.$fromName,
            'requester_phone' => $fromPhone,
            'requester_name' => $fromName,
        ]);
    }
}
