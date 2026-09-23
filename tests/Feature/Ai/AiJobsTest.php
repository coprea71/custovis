<?php

namespace Tests\Feature\Ai;

use App\Core\Ai\AiProviderFactory;
use App\Jobs\AutoTriageJob;
use App\Jobs\SuggestReplyJob;
use App\Jobs\SummarizeTicketJob;
use App\Models\AiBudget;
use App\Models\AiUsageLog;
use App\Models\Team;
use App\Models\Ticket;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\FakeAiProvider;
use Tests\TestCase;

class AiJobsTest extends TestCase
{
    use RefreshDatabase;

    private function bindFakeFactory(): void
    {
        $fake = new class extends AiProviderFactory
        {
            public function make(Team $team, string $useCase): \App\Core\Ai\Contracts\AiProviderInterface
            {
                return new FakeAiProvider;
            }

            public function providerNameFor(Team $team, string $useCase): string
            {
                return 'fake';
            }
        };

        $this->app->instance(AiProviderFactory::class, $fake);
    }

    private function makeTicket(): Ticket
    {
        $team = Team::query()->create(['name' => 'Support', 'slug' => 'support']);

        $ticket = Ticket::query()->create([
            'team_id' => $team->id,
            'type' => 'support_ticket',
            'source' => 'api',
            'subject' => 'Test',
            'requester_email' => 'kunde@example.com',
        ]);

        $ticket->messages()->create([
            'visibility' => 'public',
            'direction' => 'incoming',
            'external_author_name' => 'Kunde',
            'body_text' => 'Mein Drucker geht nicht.',
            'message_id' => 'msg-1',
        ]);

        return $ticket->fresh();
    }

    public function test_summarize_job_creates_internal_note_and_logs_usage(): void
    {
        $this->bindFakeFactory();
        $ticket = $this->makeTicket();

        (new SummarizeTicketJob($ticket))->handle(app(AiProviderFactory::class));

        $this->assertDatabaseHas('ticket_messages', [
            'ticket_id' => $ticket->id,
            'visibility' => 'internal_note',
            'external_author_name' => 'KI-Zusammenfassung',
        ]);
        $this->assertDatabaseHas('ai_usage_logs', [
            'ticket_id' => $ticket->id,
            'use_case' => 'summarize',
            'provider' => 'fake',
            'tokens_used' => 42,
        ]);
    }

    public function test_suggest_reply_job_creates_internal_note(): void
    {
        $this->bindFakeFactory();
        $ticket = $this->makeTicket();

        (new SuggestReplyJob($ticket))->handle(app(AiProviderFactory::class));

        $this->assertDatabaseHas('ticket_messages', [
            'ticket_id' => $ticket->id,
            'external_author_name' => 'KI-Antwortvorschlag',
        ]);
    }

    public function test_auto_triage_job_sets_priority_from_classification(): void
    {
        $this->bindFakeFactory();
        $ticket = $this->makeTicket();

        (new AutoTriageJob($ticket))->handle(app(AiProviderFactory::class));

        $this->assertSame('low', $ticket->refresh()->priority);
    }

    public function test_job_is_skipped_when_budget_exceeded(): void
    {
        $this->bindFakeFactory();
        $ticket = $this->makeTicket();

        AiBudget::query()->create(['team_id' => $ticket->team_id, 'monthly_limit_cents' => 100]);
        AiUsageLog::query()->create([
            'team_id' => $ticket->team_id,
            'use_case' => 'summarize',
            'provider' => 'fake',
            'tokens_used' => 1000,
            'cost_cents' => 200,
        ]);

        (new SummarizeTicketJob($ticket))->handle(app(AiProviderFactory::class));

        $this->assertDatabaseMissing('ticket_messages', [
            'ticket_id' => $ticket->id,
            'external_author_name' => 'KI-Zusammenfassung',
        ]);
    }
}
