<?php

namespace App\Core\Ai\Support;

use App\Core\Ai\AiProviderFactory;
use App\Jobs\AutoTriageJob;
use App\Jobs\FindSimilarTicketsJob;
use App\Jobs\SuggestReplyJob;
use App\Jobs\SummarizeTicketJob;
use App\Models\Team;
use App\Models\Ticket;
use App\Services\ModuleAccess;

/**
 * Decides when the AI jobs run (25.md): triage and similar tickets
 * automatically for every new ticket, summary and reply suggestion on
 * request in the ticket. A job is only queued when the ai-agent module is
 * on, the use case has a provider and the monthly budget is not used up.
 */
class AiTicketAssistant
{
    /** @var array<string, class-string> */
    private const ON_NEW_TICKET = [
        'classify' => AutoTriageJob::class,
        'embed' => FindSimilarTicketsJob::class,
    ];

    /** @var array<string, class-string> */
    public const ON_REQUEST = [
        'summarize' => SummarizeTicketJob::class,
        'suggest_reply' => SuggestReplyJob::class,
    ];

    public function __construct(
        private readonly ModuleAccess $modules,
        private readonly AiProviderFactory $factory,
        private readonly AiBudgetService $budget,
    ) {}

    public function ticketCreated(Ticket $ticket): void
    {
        if (! $this->modules->enabled('ai-agent')) {
            return;
        }

        foreach (self::ON_NEW_TICKET as $useCase => $job) {
            if ($this->canRun($ticket->team, $useCase)) {
                // After commit: the job must see the ticket and its first message.
                $job::dispatch($ticket)->afterCommit();
            }
        }
    }

    public function request(Ticket $ticket, string $useCase): bool
    {
        $job = self::ON_REQUEST[$useCase] ?? null;

        if ($job === null || ! $this->canRun($ticket->team, $useCase)) {
            return false;
        }

        $job::dispatch($ticket);

        return true;
    }

    public function canRun(Team $team, string $useCase): bool
    {
        return $this->factory->isConfigured($team, $useCase) && $this->budget->withinBudget($team);
    }
}
