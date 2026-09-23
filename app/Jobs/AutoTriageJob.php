<?php

namespace App\Jobs;

use App\Core\Ai\AiProviderFactory;
use App\Jobs\Concerns\InteractsWithAi;
use App\Models\Ticket;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class AutoTriageJob implements ShouldQueue
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

        $provider = $factory->make($this->ticket->team, 'classify');
        $text = $this->prepareText($this->ticket, 'classify', $this->conversationText($this->ticket));

        $priority = trim($provider->classify($text, Ticket::PRIORITIES));

        if (in_array($priority, Ticket::PRIORITIES, true)) {
            $this->ticket->update(['priority' => $priority]);
        }

        $this->logUsage($this->ticket, 'classify', $factory->providerNameFor($this->ticket->team, 'classify'), $provider->lastUsageTokens());
    }
}
