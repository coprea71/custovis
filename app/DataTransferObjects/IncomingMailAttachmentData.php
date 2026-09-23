<?php

namespace App\DataTransferObjects;

readonly class IncomingMailAttachmentData
{
    public function __construct(
        public string $name,
        public ?string $mimeType,
        public string $content,
    ) {
    }
}
