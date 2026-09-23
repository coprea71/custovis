<?php

namespace Tests\Feature\Mail;

use App\DataTransferObjects\IncomingMailMessageData;
use App\Models\Mailbox;
use App\Models\Team;
use App\Models\Ticket;
use App\Services\AttachmentService;
use App\Services\MailToTicketService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MailToTicketServiceTest extends TestCase
{
    use RefreshDatabase;

    private function makeMailbox(): Mailbox
    {
        $team = Team::query()->create(['name' => 'Support', 'slug' => 'support']);

        return Mailbox::query()->create([
            'team_id' => $team->id,
            'name' => 'Support Inbox',
            'email_address' => 'support@example.com',
            'imap_host' => 'imap.example.com',
            'imap_username' => 'support@example.com',
            'imap_password' => 'secret',
            'smtp_host' => 'smtp.example.com',
            'smtp_username' => 'support@example.com',
            'smtp_password' => 'secret',
        ]);
    }

    public function test_first_message_creates_a_new_ticket(): void
    {
        $mailbox = $this->makeMailbox();
        $service = new MailToTicketService(app(AttachmentService::class));

        $message = $service->import($mailbox, new IncomingMailMessageData(
            messageId: 'msg-1@customer.example',
            inReplyTo: null,
            references: [],
            subject: 'Drucker funktioniert nicht',
            fromEmail: 'kunde@example.com',
            fromName: 'Max Kunde',
            bodyHtml: '<p>Hilfe!</p>',
            bodyText: 'Hilfe!',
            attachments: [],
        ));

        $this->assertDatabaseCount('tickets', 1);
        $this->assertSame('Drucker funktioniert nicht', $message->ticket->subject);
        $this->assertSame('mailbox', $message->ticket->source);
        $this->assertSame('public', $message->visibility);
    }

    public function test_reply_referencing_message_id_is_appended_to_existing_ticket(): void
    {
        $mailbox = $this->makeMailbox();
        $service = new MailToTicketService(app(AttachmentService::class));

        $first = $service->import($mailbox, new IncomingMailMessageData(
            messageId: 'msg-1@customer.example',
            inReplyTo: null,
            references: [],
            subject: 'Drucker funktioniert nicht',
            fromEmail: 'kunde@example.com',
            fromName: 'Max Kunde',
            bodyHtml: null,
            bodyText: 'Hilfe!',
            attachments: [],
        ));

        $reply = $service->import($mailbox, new IncomingMailMessageData(
            messageId: 'msg-2@customer.example',
            inReplyTo: 'msg-1@customer.example',
            references: ['msg-1@customer.example'],
            subject: 'Re: Drucker funktioniert nicht',
            fromEmail: 'kunde@example.com',
            fromName: 'Max Kunde',
            bodyHtml: null,
            bodyText: 'Noch ein Detail.',
            attachments: [],
        ));

        $this->assertDatabaseCount('tickets', 1);
        $this->assertSame($first->ticket_id, $reply->ticket_id);
    }

    public function test_reimporting_the_same_message_id_is_idempotent(): void
    {
        $mailbox = $this->makeMailbox();
        $service = new MailToTicketService(app(AttachmentService::class));

        $data = new IncomingMailMessageData(
            messageId: 'msg-1@customer.example',
            inReplyTo: null,
            references: [],
            subject: 'Drucker funktioniert nicht',
            fromEmail: 'kunde@example.com',
            fromName: 'Max Kunde',
            bodyHtml: null,
            bodyText: 'Hilfe!',
            attachments: [],
        );

        $service->import($mailbox, $data);
        $service->import($mailbox, $data);

        $this->assertDatabaseCount('tickets', 1);
        $this->assertDatabaseCount('ticket_messages', 1);
    }

    public function test_unrelated_message_creates_a_separate_ticket(): void
    {
        $mailbox = $this->makeMailbox();
        $service = new MailToTicketService(app(AttachmentService::class));

        $service->import($mailbox, new IncomingMailMessageData(
            messageId: 'msg-1@customer.example',
            inReplyTo: null,
            references: [],
            subject: 'Drucker funktioniert nicht',
            fromEmail: 'kunde@example.com',
            fromName: 'Max Kunde',
            bodyHtml: null,
            bodyText: 'Hilfe!',
            attachments: [],
        ));

        $service->import($mailbox, new IncomingMailMessageData(
            messageId: 'msg-9@other.example',
            inReplyTo: null,
            references: [],
            subject: 'Rechnung fehlt',
            fromEmail: 'other@example.com',
            fromName: 'Other Customer',
            bodyHtml: null,
            bodyText: 'Wo ist meine Rechnung?',
            attachments: [],
        ));

        $this->assertSame(2, Ticket::query()->count());
    }
}
