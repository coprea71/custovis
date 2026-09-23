<?php

namespace App\Services;

use App\Mail\TicketReplyMail;
use App\Models\Mailbox;
use App\Models\TicketMessage;
use Illuminate\Support\Facades\Mail;

/**
 * Shared mail dispatch, built once here so later channels (WhatsApp
 * notifications, team chat digests, …) can reuse the same dynamic-mailer
 * setup instead of each wiring SMTP config on their own (DRY).
 */
class MailSenderService
{
    public function sendTicketReply(TicketMessage $message): void
    {
        $mailbox = $message->ticket->mailbox;
        $ticket = $message->ticket;

        Mail::mailer($this->dynamicMailerFor($mailbox))
            ->to($ticket->requester_email, $ticket->requester_name)
            ->queue((new TicketReplyMail($message))->onQueue('mail-send'));
    }

    private function dynamicMailerFor(Mailbox $mailbox): string
    {
        $name = 'mailbox_'.$mailbox->id;

        config(["mail.mailers.{$name}" => [
            'transport' => 'smtp',
            'host' => $mailbox->smtp_host,
            'port' => $mailbox->smtp_port,
            'encryption' => $mailbox->smtp_encryption === 'none' ? null : $mailbox->smtp_encryption,
            'username' => $mailbox->smtp_username,
            'password' => $mailbox->smtp_password,
        ]]);

        config(["mail.from" => [
            'address' => $mailbox->email_address,
            'name' => $mailbox->name,
        ]]);

        return $name;
    }
}
