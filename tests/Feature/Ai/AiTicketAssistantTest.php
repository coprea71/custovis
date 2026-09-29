<?php

namespace Tests\Feature\Ai;

use App\Jobs\AutoTriageJob;
use App\Jobs\FindSimilarTicketsJob;
use App\Jobs\SuggestReplyJob;
use App\Jobs\SummarizeTicketJob;
use App\Livewire\Agent\TicketAiPanel;
use App\Models\AiBudget;
use App\Models\AiSetting;
use App\Models\AiUsageLog;
use App\Models\Module;
use App\Models\Team;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use Tests\TestCase;

class AiTicketAssistantTest extends TestCase
{
    use RefreshDatabase;

    private Team $team;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.openai.key' => null, 'services.default_ai_provider' => 'openai']);
        Module::query()->updateOrCreate(['slug' => 'ai-agent'], ['name' => 'AiAgent', 'enabled' => true]);
        $this->team = Team::query()->create(['name' => 'Support', 'slug' => 'support']);
        Queue::fake();
    }

    private function configure(string ...$useCases): void
    {
        foreach ($useCases as $useCase) {
            AiSetting::query()->create(['team_id' => $this->team->id, 'use_case' => $useCase, 'provider' => 'openai', 'api_key' => 'sk-test']);
        }
    }

    private function createTicket(array $attributes = []): Ticket
    {
        return Ticket::query()->create([
            'team_id' => $this->team->id, 'type' => 'support_ticket', 'source' => 'api', 'subject' => 'Drucker', ...$attributes,
        ]);
    }

    public function test_new_ticket_queues_triage_and_similar_tickets(): void
    {
        $this->configure('classify', 'embed');

        $this->createTicket();

        Queue::assertPushedOn('ai-processing', AutoTriageJob::class);
        Queue::assertPushedOn('ai-processing', FindSimilarTicketsJob::class);
        Queue::assertNotPushed(SummarizeTicketJob::class);
    }

    public function test_only_configured_use_cases_are_queued(): void
    {
        $this->configure('embed');

        $this->createTicket();

        Queue::assertNotPushed(AutoTriageJob::class);
        Queue::assertPushed(FindSimilarTicketsJob::class);
    }

    public function test_env_fallback_counts_as_configured(): void
    {
        config(['services.openai.key' => 'sk-env']);

        $this->createTicket();

        Queue::assertPushed(AutoTriageJob::class);
    }

    public function test_nothing_is_queued_when_module_is_disabled(): void
    {
        $this->configure('classify', 'embed');
        Module::query()->where('slug', 'ai-agent')->update(['enabled' => false]);

        $this->createTicket();

        Queue::assertNothingPushed();
    }

    public function test_nothing_is_queued_when_budget_is_used_up(): void
    {
        $this->configure('classify', 'embed');
        AiBudget::query()->create(['team_id' => $this->team->id, 'monthly_limit_cents' => 100]);
        AiUsageLog::query()->create(['team_id' => $this->team->id, 'use_case' => 'classify', 'provider' => 'openai', 'tokens_used' => 1, 'cost_cents' => 100]);

        $this->createTicket();

        Queue::assertNothingPushed();
    }

    public function test_spam_ticket_is_never_sent_to_ai(): void
    {
        $this->configure('classify', 'embed');

        $this->createTicket(['spam_at' => now()]);

        Queue::assertNothingPushed();
    }

    public function test_agent_requests_summary_and_reply_suggestion(): void
    {
        $this->configure('summarize', 'suggest_reply');
        $ticket = $this->createTicket();
        $user = User::factory()->create();
        $this->team->users()->attach($user, ['role_in_team' => 'member']);

        Livewire::actingAs($user)
            ->test(TicketAiPanel::class, ['ticketId' => $ticket->id])
            ->assertSee('Zusammenfassen')
            ->call('request', 'summarize')
            ->call('request', 'suggest_reply')
            ->assertSee('als interne Notiz');

        Queue::assertPushedOn('ai-processing', SummarizeTicketJob::class);
        Queue::assertPushedOn('ai-processing', SuggestReplyJob::class);
    }

    public function test_unknown_or_unconfigured_use_case_is_not_queued(): void
    {
        $ticket = $this->createTicket();
        $user = User::factory()->create();
        $this->team->users()->attach($user, ['role_in_team' => 'member']);

        Livewire::actingAs($user)
            ->test(TicketAiPanel::class, ['ticketId' => $ticket->id])
            ->assertDontSee('Zusammenfassen')
            ->call('request', 'summarize')
            ->call('request', 'classify')
            ->assertSee('Nicht verfügbar');

        Queue::assertNothingPushed();
    }

    public function test_agent_of_another_team_cannot_use_the_panel(): void
    {
        $this->configure('summarize');
        $ticket = $this->createTicket();
        $stranger = User::factory()->create();

        Livewire::actingAs($stranger)
            ->test(TicketAiPanel::class, ['ticketId' => $ticket->id])
            ->assertForbidden();

        Queue::assertNothingPushed();
    }
}
