<?php

namespace App\Services;

use App\Models\TicketAttachment;
use App\Models\TicketMessage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Shared attachment storage used by every channel that produces ticket
 * attachments (mailbox, external API, WhatsApp, team chat) — one storage
 * path/naming convention instead of duplicating it per channel.
 */
class AttachmentService
{
    private const DISK = 'local';

    public function storeUploadedFile(TicketMessage $message, UploadedFile $file): TicketAttachment
    {
        $path = $file->store($this->directoryFor($message), self::DISK);

        return $message->attachments()->create([
            'disk' => self::DISK,
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getClientMimeType(),
            'size_bytes' => $file->getSize(),
        ]);
    }

    public function storeRawContent(TicketMessage $message, string $content, string $originalName, ?string $mimeType): TicketAttachment
    {
        $path = $this->directoryFor($message).'/'.Str::random(20).'_'.$originalName;

        Storage::disk(self::DISK)->put($path, $content);

        return $message->attachments()->create([
            'disk' => self::DISK,
            'path' => $path,
            'original_name' => $originalName,
            'mime_type' => $mimeType,
            'size_bytes' => strlen($content),
        ]);
    }

    private function directoryFor(TicketMessage $message): string
    {
        return "ticket-attachments/{$message->ticket_id}";
    }
}
