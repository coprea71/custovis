<?php

namespace App\Jobs;

use App\Core\Ai\AiProviderFactory;
use App\Jobs\Concerns\InteractsWithAi;
use App\Models\Ticket;
use App\Models\TicketMessage;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SummarizeTicketJob implements ShouldQueue
{
    use InteractsWithAi, Queueable;

    public function __construct(public Ticket $ticket)
    {
        $this->onQueue('ai-processing');
    }

    public function handle(AiProviderFactory $factory): void
    {
        if (! $this->budgetAllows($this->ticket)) {
            return;
        }

        $provider = $factory->make($this->ticket->team, 'summarize');
        $text = $this->prepareText($this->ticket, 'summarize', $this->conversationText($this->ticket));

        $summary = $provider->summarize($text);

        $this->ticket->messages()->create([
            'visibility' => TicketMessage::VISIBILITY_INTERNAL_NOTE,
            'direction' => 'outgoing',
            'external_author_name' => 'KI-Zusammenfassung',
            'body_text' => $summary,
            'message_id' => 'ai-summary-'.uniqid('', true),
        ]);

        $this->logUsage($this->ticket, 'summarize', $factory->providerNameFor($this->ticket->team, 'summarize'), $provider->lastUsageTokens());
    }
}
