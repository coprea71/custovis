<?php

namespace App\Jobs;

use App\Core\Ai\AiProviderFactory;
use App\Jobs\Concerns\InteractsWithAi;
use App\Models\Ticket;
use App\Models\TicketMessage;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SuggestReplyJob implements ShouldQueue
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

        $provider = $factory->make($this->ticket->team, 'suggest_reply');
        $text = $this->prepareText($this->ticket, 'suggest_reply', $this->conversationText($this->ticket));

        $suggestion = $provider->suggestReply($text);

        $this->ticket->messages()->create([
            'visibility' => TicketMessage::VISIBILITY_INTERNAL_NOTE,
            'direction' => 'outgoing',
            'external_author_name' => 'KI-Antwortvorschlag',
            'body_text' => $suggestion,
            'message_id' => 'ai-suggestion-'.uniqid('', true),
        ]);

        $this->logUsage($this->ticket, 'suggest_reply', $factory->providerNameFor($this->ticket->team, 'suggest_reply'), $provider->lastUsageTokens());
    }
}
