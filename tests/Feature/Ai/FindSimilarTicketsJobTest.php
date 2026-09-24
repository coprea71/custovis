<?php

namespace Tests\Feature\Ai;

use App\Core\Ai\AiProviderFactory;
use App\Core\Ai\Contracts\AiProviderInterface;
use App\Jobs\FindSimilarTicketsJob;
use App\Models\Team;
use App\Models\Ticket;
use App\Models\TicketEmbedding;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FindSimilarTicketsJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_stores_embedding_and_flags_similar_ticket(): void
    {
        $team = Team::query()->create(['name' => 'Support', 'slug' => 'support']);

        $existing = Ticket::query()->create([
            'team_id' => $team->id, 'type' => 'support_ticket', 'source' => 'api', 'subject' => 'Alt',
        ]);
        TicketEmbedding::query()->create([
            'ticket_id' => $existing->id,
            'vector' => [1.0, 0.0, 0.0],
            'provider' => 'fake',
        ]);

        $ticket = Ticket::query()->create([
            'team_id' => $team->id, 'type' => 'support_ticket', 'source' => 'api', 'subject' => 'Neu',
        ]);

        $fakeProvider = new class implements AiProviderInterface
        {
            public function summarize(string $text): string
            {
                return '';
            }

            public function suggestReply(string $conversationContext): string
            {
                return '';
            }

            public function classify(string $text, array $labels): string
            {
                return '';
            }

            public function embed(string $text): array
            {
                return [1.0, 0.0, 0.0];
            }

            public function lastUsageTokens(): int
            {
                return 5;
            }
        };

        $factory = new class($fakeProvider) extends AiProviderFactory
        {
            public function __construct(private AiProviderInterface $provider) {}

            public function make(Team $team, string $useCase): AiProviderInterface
            {
                return $this->provider;
            }

            public function providerNameFor(Team $team, string $useCase): string
            {
                return 'fake';
            }
        };

        (new FindSimilarTicketsJob($ticket))->handle($factory);

        $this->assertDatabaseHas('ticket_embeddings', ['ticket_id' => $ticket->id]);
        $this->assertDatabaseHas('ticket_messages', [
            'ticket_id' => $ticket->id,
            'body_text' => "Ähnliche Tickets: #{$existing->id}",
        ]);
    }
}
