<?php

namespace Tests\Feature\Mail;

use App\DataTransferObjects\IncomingMailMessageData;
use App\Models\Mailbox;
use App\Models\SlaPolicy;
use App\Services\MailToTicketService;
use App\Support\MailPriority;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;
use Webklex\PHPIMAP\Message;

class MailPriorityTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{0: string, 1: string|null}>
     */
    public static function headers(): array
    {
        return [
            'Outlook X-Priority 1' => ["X-Priority: 1 (Highest)\r\n", 'high'],
            'Thunderbird X-Priority 2' => ["X-Priority: 2 (High)\r\n", 'high'],
            'Importance high' => ["Importance: High\r\n", 'high'],
            'X-MSMail-Priority' => ["X-MSMail-Priority: High\r\n", 'high'],
            'RFC 2156 urgent' => ["Priority: urgent\r\n", 'urgent'],
            'normal' => ["X-Priority: 3 (Normal)\r\n", null],
            'low is ignored' => ["X-Priority: 5 (Lowest)\r\nImportance: low\r\n", null],
            'no header' => ['', null],
        ];
    }

    #[DataProvider('headers')]
    public function test_priority_is_read_from_mail_headers(string $header, ?string $expected): void
    {
        $raw = "From: Kunde <kunde@example.com>\r\nTo: support@example.com\r\nSubject: Hilfe\r\nMessage-ID: <a@example.com>\r\n"
            .$header."Content-Type: text/plain; charset=utf-8\r\n\r\nServer steht.\r\n";

        $this->assertSame($expected, MailPriority::fromMessage(Message::fromString($raw)));
    }

    public function test_high_priority_mail_creates_high_ticket_with_matching_sla_and_raises_follow_ups(): void
    {
        $mailbox = Mailbox::factory()->create();
        SlaPolicy::query()->create(['team_id' => $mailbox->team_id, 'name' => 'Hoch', 'priority' => 'high', 'response_time_minutes' => 60, 'resolution_time_minutes' => 240]);
        $service = app(MailToTicketService::class);

        $ticket = $service->import($mailbox, $this->mail('m1@example.com', null, 'high'))->ticket;
        $this->assertSame('high', $ticket->priority);
        $this->assertNotNull($ticket->fresh()->sla_policy_id);

        $service->import($mailbox, $this->mail('m2@example.com', 'm1@example.com', null));
        $this->assertSame('high', $ticket->fresh()->priority, 'a normal follow-up never lowers the priority');

        $service->import($mailbox, $this->mail('m3@example.com', 'm1@example.com', 'urgent'));
        $this->assertSame('urgent', $ticket->fresh()->priority);
    }

    public function test_mail_without_priority_creates_normal_ticket(): void
    {
        $ticket = app(MailToTicketService::class)->import(Mailbox::factory()->create(), $this->mail('n1@example.com', null, null))->ticket;

        $this->assertSame('normal', $ticket->priority);
    }

    private function mail(string $messageId, ?string $inReplyTo, ?string $priority): IncomingMailMessageData
    {
        return new IncomingMailMessageData(
            messageId: $messageId, inReplyTo: $inReplyTo, references: [], subject: 'Server steht',
            fromEmail: 'kunde@example.com', fromName: 'Kunde', bodyHtml: null, bodyText: 'Hilfe', attachments: [], priority: $priority,
        );
    }
}
