<?php

namespace App\Services\Dashboard;

use App\Models\AiUsageLog;
use App\Models\KnowledgeBaseArticleFeedback;
use App\Models\Team;
use App\Models\Ticket;
use App\Models\TicketChange;
use App\States\Change\Closed;
use App\States\Change\Rejected;
use Illuminate\Database\Eloquent\Builder;

/**
 * Aggregations shared by the management dashboard (team = null, all teams)
 * and the team dashboard (team given) — one code path, only the scope differs.
 */
class DashboardMetrics
{
    private const SLA_WINDOW_DAYS = 30;

    private const VOLUME_DAYS = 14;

    /**
     * @return array<string, mixed>
     */
    public function compute(?Team $team): array
    {
        $metrics = [
            'open_by_type' => $this->countBy($this->openTickets($team), 'type'),
            'open_by_priority' => $this->countBy($this->openTickets($team), 'priority'),
            'overdue_by_type' => $this->countBy($this->overdueTickets($team), 'type'),
            'sla_compliance' => $this->slaCompliance($team),
            'change_success' => $this->changeSuccess($team),
            'volume' => $this->volume($team),
            'ai_usage' => $this->aiUsage($team),
        ];

        return $team
            ? $metrics + ['agent_load' => $this->agentLoad($team)]
            : $metrics + ['open_by_team' => $this->openByTeam(), 'kb_feedback' => $this->knowledgeBaseFeedback()];
    }

    /**
     * @param  Builder<Ticket>  $query
     * @return array<string, int>
     */
    private function countBy(Builder $query, string $column): array
    {
        return $query->groupBy($column)
            ->selectRaw("{$column} as label, count(*) as total")
            ->pluck('total', 'label')
            ->map(fn ($total) => (int) $total)
            ->all();
    }

    /**
     * @return Builder<Ticket>
     */
    private function tickets(?Team $team): Builder
    {
        return Ticket::query()->when($team, fn (Builder $query) => $query->where('team_id', $team->id));
    }

    /**
     * @return Builder<Ticket>
     */
    private function openTickets(?Team $team): Builder
    {
        return $this->tickets($team)->where('status', '!=', 'closed');
    }

    /**
     * @return Builder<Ticket>
     */
    private function overdueTickets(?Team $team): Builder
    {
        return $this->openTickets($team)->where(fn (Builder $query) => $query
            ->whereNotNull('sla_breached_at')
            ->orWhere('sla_resolution_due_at', '<', now()));
    }

    /**
     * @return array{total: int, rate: float|null}
     */
    private function slaCompliance(?Team $team): array
    {
        $withSla = $this->tickets($team)
            ->whereNotNull('sla_policy_id')
            ->where('created_at', '>=', now()->subDays(self::SLA_WINDOW_DAYS));
        $total = (clone $withSla)->count();
        $met = (clone $withSla)->whereNull('sla_breached_at')->count();

        return ['total' => $total, 'rate' => $total > 0 ? round($met / $total * 100, 1) : null];
    }

    /**
     * Success = closed after implementation; rejected by the CAB counts as
     * not successful. Changes still in flight are ignored.
     *
     * @return array{total: int, rate: float|null}
     */
    private function changeSuccess(?Team $team): array
    {
        $changes = TicketChange::query()->when($team, fn (Builder $query) => $query
            ->whereHas('ticket', fn (Builder $ticket) => $ticket->where('team_id', $team->id)));
        $closed = (clone $changes)->whereState('state', Closed::class)->count();
        $total = $closed + (clone $changes)->whereState('state', Rejected::class)->count();

        return ['total' => $total, 'rate' => $total > 0 ? round($closed / $total * 100, 1) : null];
    }

    /**
     * @return array<string, int> date (Y-m-d) => created tickets, gap-free
     */
    private function volume(?Team $team): array
    {
        $start = now()->subDays(self::VOLUME_DAYS - 1)->startOfDay();
        $counts = $this->tickets($team)
            ->where('created_at', '>=', $start)
            ->selectRaw('DATE(created_at) as day, count(*) as total')
            ->groupBy('day')
            ->pluck('total', 'day');

        return collect(range(0, self::VOLUME_DAYS - 1))
            ->map(fn (int $offset) => $start->copy()->addDays($offset)->toDateString())
            ->mapWithKeys(fn (string $day) => [$day => (int) ($counts[$day] ?? 0)])
            ->all();
    }

    /**
     * @return array{tokens: int, cost_cents: int}
     */
    private function aiUsage(?Team $team): array
    {
        $usage = AiUsageLog::query()
            ->when($team, fn (Builder $query) => $query->where('team_id', $team->id))
            ->where('created_at', '>=', now()->startOfMonth())
            ->selectRaw('coalesce(sum(tokens_used), 0) as tokens, coalesce(sum(cost_cents), 0) as cost_cents')
            ->first();

        return ['tokens' => (int) $usage->tokens, 'cost_cents' => (int) $usage->cost_cents];
    }

    /**
     * @return array<string, int> agent name => open assigned tickets (every member listed, also with 0)
     */
    private function agentLoad(Team $team): array
    {
        $load = $this->openTickets($team)
            ->whereNotNull('assigned_to')
            ->groupBy('assigned_to')
            ->selectRaw('assigned_to, count(*) as total')
            ->pluck('total', 'assigned_to');

        return $team->users()->orderBy('name')->get()
            ->mapWithKeys(fn ($user) => [$user->name => (int) ($load[$user->id] ?? 0)])
            ->all();
    }

    /**
     * Knowledge base is installation-wide, so only the management view shows it.
     *
     * @return array{helpful: int, not_helpful: int}
     */
    private function knowledgeBaseFeedback(): array
    {
        $counts = KnowledgeBaseArticleFeedback::query()
            ->groupBy('helpful')
            ->selectRaw('helpful, count(*) as total')
            ->pluck('total', 'helpful');

        return ['helpful' => (int) ($counts[1] ?? 0), 'not_helpful' => (int) ($counts[0] ?? 0)];
    }

    /**
     * @return array<string, int>
     */
    private function openByTeam(): array
    {
        $counts = $this->openTickets(null)
            ->groupBy('team_id')
            ->selectRaw('team_id, count(*) as total')
            ->pluck('total', 'team_id');

        return Team::query()->orderBy('name')->get()
            ->mapWithKeys(fn (Team $team) => [$team->name => (int) ($counts[$team->id] ?? 0)])
            ->all();
    }
}
