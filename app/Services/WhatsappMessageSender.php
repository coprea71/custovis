<?php

namespace App\Services;

use App\Exceptions\WhatsappSessionWindowExpiredException;
use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Models\WhatsappTemplate;
use Illuminate\Support\Facades\Http;

/**
 * Outbound WhatsApp sends, mirroring MailSenderService's shape (2.md) so
 * both channels dispatch the same way from the agent workspace.
 */
class WhatsappMessageSender
{
    private const SESSION_WINDOW_HOURS = 24;

    public function sendFreeText(TicketMessage $message): void
    {
        $ticket = $message->ticket;

        if (! $this->isWithinSessionWindow($ticket)) {
            throw new WhatsappSessionWindowExpiredException;
        }

        $this->post($ticket, [
            'messaging_product' => 'whatsapp',
            'to' => $ticket->requester_phone,
            'type' => 'text',
            'text' => ['body' => $message->body_text],
        ]);
    }

    public function sendTemplate(TicketMessage $message, WhatsappTemplate $template): void
    {
        $ticket = $message->ticket;

        $this->post($ticket, [
            'messaging_product' => 'whatsapp',
            'to' => $ticket->requester_phone,
            'type' => 'template',
            'template' => [
                'name' => $template->name,
                'language' => ['code' => $template->language],
            ],
        ]);
    }

    public function isWithinSessionWindow(Ticket $ticket): bool
    {
        $lastInbound = $ticket->messages()
            ->where('direction', 'incoming')
            ->latest()
            ->first();

        if (! $lastInbound) {
            return false;
        }

        return $lastInbound->created_at->diffInHours(now()) < self::SESSION_WINDOW_HOURS;
    }

    private function post(Ticket $ticket, array $payload): void
    {
        $account = $ticket->whatsappAccount;

        Http::withToken($account->access_token)
            ->post("https://graph.facebook.com/v20.0/{$account->phone_number_id}/messages", $payload)
            ->throw();
    }
}
