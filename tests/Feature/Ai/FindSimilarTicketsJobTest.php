<?php

namespace Tests\Feature\Ai;

use App\Core\Ai\AiProviderFactory;
use App\Core\Ai\Contracts\AiProviderInterface;
use App\Jobs\FindSimilarTicketsJob;
use App\Models\Team;
use App\Models\Ticket;
use App\Models\TicketEmbedding;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\FakeAiProvider;
use Tests\TestCase;

class FindSimilarTicketsJobTest extends TestCase
{
    use RefreshDatabase;

    private function fakeFactory(): AiProviderFactory
    {
        return new class extends AiProviderFactory
        {
            public function make(Team $team, string $useCase): AiProviderInterface
            {
                return new FakeAiProvider;
            }

            public function providerNameFor(Team $team, string $useCase): string
            {
                return 'fake';
            }
        };
    }

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

        (new FindSimilarTicketsJob($ticket))->handle($this->fakeFactory());

        $this->assertDatabaseHas('ticket_embeddings', ['ticket_id' => $ticket->id]);
        $this->assertDatabaseHas('ticket_messages', [
            'ticket_id' => $ticket->id,
            'body_text' => "Ähnliche Tickets: #{$existing->id}",
        ]);
    }

    public function test_similar_tickets_of_other_teams_are_never_named(): void
    {
        $team = Team::query()->create(['name' => 'Support', 'slug' => 'support']);
        $otherTeam = Team::query()->create(['name' => 'Vertrieb', 'slug' => 'vertrieb']);

        $foreign = Ticket::query()->create([
            'team_id' => $otherTeam->id, 'type' => 'support_ticket', 'source' => 'api', 'subject' => 'Fremd',
        ]);
        TicketEmbedding::query()->create(['ticket_id' => $foreign->id, 'vector' => [1.0, 0.0, 0.0], 'provider' => 'fake']);

        $ticket = Ticket::query()->create([
            'team_id' => $team->id, 'type' => 'support_ticket', 'source' => 'api', 'subject' => 'Neu',
        ]);

        (new FindSimilarTicketsJob($ticket))->handle($this->fakeFactory());

        $this->assertDatabaseMissing('ticket_messages', ['ticket_id' => $ticket->id, 'external_author_name' => 'KI: Ähnliche Tickets']);
    }
}
