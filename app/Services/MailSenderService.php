<?php

namespace App\Services;

use App\Mail\InvoiceMail;
use App\Mail\TicketReplyMail;
use App\Models\Invoice;
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
    // Sends synchronously: callers already run inside a queued job, and the
    // runtime-registered mailer config would not survive a second queue hop
    // into a later worker process.
    public function sendTicketReply(TicketMessage $message): void
    {
        $mailbox = $message->ticket->mailbox;
        $ticket = $message->ticket;

        Mail::mailer($this->dynamicMailerFor($mailbox))
            ->to($ticket->requester_email, $ticket->requester_name)
            ->send(new TicketReplyMail($message));
    }

    /**
     * Invoices go out through the mailbox chosen in the invoicing settings,
     * otherwise through the system mailer (MAIL_* in .env).
     */
    public function sendInvoice(Invoice $invoice, ?Mailbox $mailbox): void
    {
        Mail::mailer($mailbox ? $this->dynamicMailerFor($mailbox) : null)
            ->to($invoice->buyer['email'], $invoice->buyer['name'])
            ->send(new InvoiceMail($invoice));
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
            // Per-mailer sender, so the global mail.from of system mails stays untouched.
            'from' => [
                'address' => $mailbox->email_address,
                'name' => $mailbox->name,
            ],
        ]]);

        // A long-running worker caches resolved mailers; drop it so edited SMTP settings apply.
        Mail::purge($name);

        return $name;
    }
}
