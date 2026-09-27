<?php

namespace App\Mail;

use App\Models\TeamMailLayout;
use App\Models\TicketAttachment;
use App\Models\TicketMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TicketReplyMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public TicketMessage $message) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '[Ticket #'.$this->message->ticket_id.'] '.$this->message->ticket->subject,
        );
    }

    public function content(): Content
    {
        $ticket = $this->message->ticket;
        $layout = TeamMailLayout::forTeam($ticket->team);

        return new Content(
            view: 'mail.ticket-reply',
            text: 'mail.ticket-reply-text',
            with: [
                'body' => $this->message->body_html ?? nl2br(e($this->message->body_text)),
                'bodyText' => $this->message->body_text ?? strip_tags((string) $this->message->body_html),
                'layout' => $layout,
                'signature' => $layout->renderSignature([
                    '{agent_name}' => (string) $this->message->authorUser?->name,
                    '{team_name}' => $ticket->team->name,
                    '{mailbox_email}' => (string) $ticket->mailbox?->email_address,
                    '{ticket_id}' => (string) $ticket->id,
                ]),
            ],
        );
    }

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return $this->message->attachments->map(
            fn (TicketAttachment $attachment) => Attachment::fromStorageDisk($attachment->disk, $attachment->path)
                ->as($attachment->original_name)
                ->withMime($attachment->mime_type ?? 'application/octet-stream')
        )->all();
    }
}
