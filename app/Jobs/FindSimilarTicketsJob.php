<?php

namespace App\Jobs;

use App\Core\Ai\AiProviderFactory;
use App\Core\Ai\Support\CosineSimilarity;
use App\Jobs\Concerns\InteractsWithAi;
use App\Models\Ticket;
use App\Models\TicketEmbedding;
use App\Models\TicketMessage;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Collection;

/**
 * Similarity via cosine distance computed in PHP over stored embeddings —
 * fine at this scale, avoids requiring a dedicated vector DB server (6.md).
 */
class FindSimilarTicketsJob implements ShouldQueue
{
    use InteractsWithAi, Queueable;

    private const TOP_N = 5;

    private const MIN_SIMILARITY = 0.75;

    public function __construct(public Ticket $ticket)
    {
        $this->onQueue('ai-processing');
    }

    public function handle(AiProviderFactory $factory): void
    {
        if (! $this->budgetAllows($this->ticket)) {
            return;
        }

        $provider = $factory->make($this->ticket->team, 'embed');
        $providerName = $factory->providerNameFor($this->ticket->team, 'embed');
        $text = $this->prepareText($this->ticket, 'embed', $this->ticket->subject."\n\n".$this->conversationText($this->ticket));

        $vector = $provider->embed($text);

        TicketEmbedding::query()->updateOrCreate(
            ['ticket_id' => $this->ticket->id],
            ['vector' => $vector, 'provider' => $providerName]
        );

        $this->logUsage($this->ticket, 'embed', $providerName, $provider->lastUsageTokens());

        $similar = $this->findSimilar($vector);

        if ($similar->isNotEmpty()) {
            $this->ticket->messages()->create([
                'visibility' => TicketMessage::VISIBILITY_INTERNAL_NOTE,
                'direction' => 'outgoing',
                'external_author_name' => 'KI: Ähnliche Tickets',
                'body_text' => 'Ähnliche Tickets: '.$similar->map(fn ($id) => "#{$id}")->implode(', '),
                'message_id' => 'ai-similar-'.uniqid('', true),
            ]);
        }
    }

    /**
     * @param  array<int, float>  $vector
     * @return Collection<int, int>
     */
    private function findSimilar(array $vector)
    {
        return TicketEmbedding::query()
            ->where('ticket_id', '!=', $this->ticket->id)
            ->get()
            ->map(fn (TicketEmbedding $embedding) => [
                'ticket_id' => $embedding->ticket_id,
                'similarity' => CosineSimilarity::between($vector, $embedding->vector),
            ])
            ->filter(fn (array $row) => $row['similarity'] >= self::MIN_SIMILARITY)
            ->sortByDesc('similarity')
            ->take(self::TOP_N)
            ->pluck('ticket_id')
            ->values();
    }

    protected function aiUseCase(): string
    {
        return 'embed';
    }
}
