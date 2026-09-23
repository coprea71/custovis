<?php

namespace App\Jobs;

use App\Models\TicketMessage;
use App\Services\WhatsappMessageSender;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendWhatsappReplyJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public TicketMessage $message)
    {
        $this->onQueue('whatsapp');
    }

    public function handle(WhatsappMessageSender $sender): void
    {
        $sender->sendFreeText($this->message);
    }
}
