<?php

namespace App\Jobs;

use App\Models\WhatsappAccount;
use App\Services\WhatsappImportService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ProcessWhatsappWebhookJob implements ShouldQueue
{
    use Queueable;

    /**
     * @param  array<string, mixed>  $value
     * @param  array<string, mixed>  $message
     */
    public function __construct(public WhatsappAccount $account, public array $value, public array $message)
    {
        $this->onQueue('whatsapp');
    }

    public function handle(WhatsappImportService $importer): void
    {
        $contact = collect($this->value['contacts'] ?? [])->first();
        $fromName = $contact['profile']['name'] ?? $this->message['from'];

        $type = $this->message['type'];
        $text = match ($type) {
            'text' => $this->message['text']['body'] ?? null,
            default => null,
        };

        $importer->importInboundMessage(
            account: $this->account,
            waMessageId: $this->message['id'],
            type: $type,
            text: $text,
            fromPhone: $this->message['from'],
            fromName: $fromName,
        );
    }
}
