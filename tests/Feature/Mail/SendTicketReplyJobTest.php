<?php

namespace Tests\Feature\Mail;

use App\Jobs\SendTicketReplyJob;
use App\Mail\TicketReplyMail;
use App\Models\Mailbox;
use App\Models\Ticket;
use App\Models\TicketMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class SendTicketReplyJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_reply_is_sent_directly_through_the_ticket_mailbox(): void
    {
        Mail::fake();
        $mailbox = Mailbox::factory()->create(['email_address' => 'support@team.example', 'name' => 'Team Support']);
        $message = $this->replyFor($mailbox);

        dispatch_sync(new SendTicketReplyJob($message));

        Mail::assertSent(TicketReplyMail::class, fn (TicketReplyMail $mail) => $mail->mailer === 'mailbox_'.$mailbox->id
            && $mail->hasTo('kunde@example.com'));
        Mail::assertNothingQueued();
        $this->assertSame('support@team.example', config("mail.mailers.mailbox_{$mailbox->id}.from.address"));
        $this->assertSame($mailbox->smtp_host, config("mail.mailers.mailbox_{$mailbox->id}.host"));
    }

    public function test_reply_does_not_override_the_global_sender(): void
    {
        Mail::fake();
        $globalFrom = config('mail.from');

        dispatch_sync(new SendTicketReplyJob($this->replyFor(Mailbox::factory()->create())));

        $this->assertSame($globalFrom, config('mail.from'));
    }

    public function test_reply_carries_its_stored_message_id_and_threads_to_the_customer_mail(): void
    {
        $mailbox = Mailbox::factory()->create();
        $reply = $this->replyFor($mailbox);
        $reply->ticket->messages()->create([
            'visibility' => TicketMessage::VISIBILITY_PUBLIC,
            'direction' => 'incoming',
            'body_text' => 'Hilfe!',
            'message_id' => 'msg-1@customer.example',
        ]);
        $reply->update(['message_id' => 'custovis-abc@support.example']);

        $headers = (new TicketReplyMail($reply->fresh()))->headers();

        $this->assertSame('custovis-abc@support.example', $headers->messageId);
        $this->assertSame(['msg-1@customer.example'], $headers->references);
        $this->assertSame('<msg-1@customer.example>', $headers->text['In-Reply-To']);
    }

    public function test_subject_never_contains_the_tag_of_another_ticket(): void
    {
        $reply = $this->replyFor(Mailbox::factory()->create());
        $reply->ticket->update(['subject' => 'RE: [Ticket #3] sql error']);

        $subject = (new TicketReplyMail($reply->fresh()))->envelope()->subject;

        $this->assertSame('[Ticket #'.$reply->ticket_id.'] RE: sql error', $subject);
    }

    private function replyFor(Mailbox $mailbox): TicketMessage
    {
        $ticket = Ticket::query()->create([
            'team_id' => $mailbox->team_id,
            'mailbox_id' => $mailbox->id,
            'source' => 'mailbox',
            'subject' => 'Druckerproblem',
            'requester_email' => 'kunde@example.com',
        ]);

        return $ticket->messages()->create([
            'visibility' => TicketMessage::VISIBILITY_PUBLIC,
            'direction' => 'outgoing',
            'body_text' => 'Wir kümmern uns darum.',
        ]);
    }
}
