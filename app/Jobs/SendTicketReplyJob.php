<?php

namespace App\Jobs;

use App\Models\TicketMessage;
use App\Services\MailSenderService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendTicketReplyJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public TicketMessage $message)
    {
        $this->onQueue('mail-send');
    }

    public function handle(MailSenderService $mailSender): void
    {
        $mailSender->sendTicketReply($this->message);
    }
}
