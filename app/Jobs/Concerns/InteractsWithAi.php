<?php

namespace App\Jobs\Concerns;

use App\Core\Ai\AiProviderFactory;
use App\Core\Ai\Support\AiBudgetService;
use App\Core\Ai\Support\AiUsageLogger;
use App\Core\Ai\Support\PiiRedactor;
use App\Models\Ticket;

trait InteractsWithAi
{
    protected function budgetAllows(Ticket $ticket): bool
    {
        return app(AiBudgetService::class)->withinBudget($ticket->team);
    }

    protected function prepareText(Ticket $ticket, string $useCase, string $text): string
    {
        $factory = app(AiProviderFactory::class);

        return $factory->redactPiiFor($ticket->team, $useCase)
            ? app(PiiRedactor::class)->redact($text)
            : $text;
    }

    protected function logUsage(Ticket $ticket, string $useCase, string $provider, int $tokens): void
    {
        app(AiUsageLogger::class)->log($ticket->team, $useCase, $provider, $tokens, $ticket);
    }

    protected function conversationText(Ticket $ticket): string
    {
        return $ticket->messages
            ->where('visibility', 'public')
            ->map(fn ($message) => ($message->external_author_name ?? $message->authorUser?->name ?? 'Kunde').': '.$message->body_text)
            ->implode("\n\n");
    }
}
