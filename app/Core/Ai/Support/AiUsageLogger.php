<?php

namespace App\Core\Ai\Support;

use App\Models\AiUsageLog;
use App\Models\Team;
use App\Models\Ticket;

class AiUsageLogger
{
    public function log(Team $team, string $useCase, string $provider, int $tokensUsed, ?Ticket $ticket = null): void
    {
        $ratePerThousand = (int) config("services.{$provider}.cost_per_1k_tokens_cents", 0);

        AiUsageLog::query()->create([
            'team_id' => $team->id,
            'ticket_id' => $ticket?->id,
            'use_case' => $useCase,
            'provider' => $provider,
            'tokens_used' => $tokensUsed,
            'cost_cents' => (int) round($tokensUsed / 1000 * $ratePerThousand),
        ]);
    }
}
