<?php

namespace App\DataTransferObjects;

readonly class IncomingMailMessageData
{
    /**
     * @param  array<int, string>  $references
     * @param  array<int, IncomingMailAttachmentData>  $attachments
     */
    public function __construct(
        public string $messageId,
        public ?string $inReplyTo,
        public array $references,
        public string $subject,
        public string $fromEmail,
        public string $fromName,
        public ?string $bodyHtml,
        public ?string $bodyText,
        public array $attachments,
    ) {}
}
