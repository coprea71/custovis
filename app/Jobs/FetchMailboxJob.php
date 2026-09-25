<?php

namespace App\Jobs;

use App\DataTransferObjects\IncomingMailAttachmentData;
use App\DataTransferObjects\IncomingMailMessageData;
use App\Models\Mailbox;
use App\Services\MailboxImapService;
use App\Services\MailToTicketService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;
use Webklex\PHPIMAP\Message;

class FetchMailboxJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public Mailbox $mailbox)
    {
        $this->onQueue('mail-fetch');
    }

    public function handle(MailToTicketService $mailToTicket, MailboxImapService $imap): void
    {
        if (! $this->mailbox->active) {
            return;
        }

        try {
            $messages = $imap->connect($this->mailbox)->getFolder('INBOX')->query()->unseen()->get();
        } catch (Throwable $e) {
            $this->mailbox->update([
                'last_fetch_error' => Str::limit($e->getMessage(), 500),
                'last_fetch_error_at' => now(),
            ]);

            throw $e;
        }

        foreach ($messages as $message) {
            try {
                $mailToTicket->import($this->mailbox, $this->toIncomingMailData($message));
                $message->setFlag('Seen');
            } catch (Throwable $e) {
                Log::error("FetchMailboxJob: failed to import message [{$message->getMessageId()}] for mailbox [{$this->mailbox->id}]: {$e->getMessage()}");
            }
        }

        $this->mailbox->update([
            'last_fetched_at' => now(),
            'last_fetch_error' => null,
            'last_fetch_error_at' => null,
        ]);
    }

    private function toIncomingMailData(Message $message): IncomingMailMessageData
    {
        $from = $message->getFrom()->first();

        return new IncomingMailMessageData(
            messageId: $this->stripAngleBrackets((string) $message->getMessageId()),
            inReplyTo: $this->stripAngleBrackets((string) $message->getInReplyTo()) ?: null,
            references: array_map(
                fn (string $ref) => $this->stripAngleBrackets($ref),
                array_filter(preg_split('/\s+/', (string) $message->getReferences()) ?: [])
            ),
            subject: (string) $message->getSubject(),
            fromEmail: $from?->mail ?? '',
            fromName: $from?->personal ?: ($from?->mail ?? ''),
            bodyHtml: $message->getHTMLBody() ?: null,
            bodyText: $message->getTextBody() ?: null,
            attachments: $message->getAttachments()->map(
                fn ($attachment) => new IncomingMailAttachmentData(
                    name: $attachment->getName() ?? 'attachment',
                    mimeType: $attachment->getMimeType(),
                    content: $attachment->getContent(),
                )
            )->all(),
        );
    }

    private function stripAngleBrackets(string $value): string
    {
        return trim($value, "<> \t\n\r\0\x0B");
    }
}
