<?php

namespace App\Livewire\Admin;

use App\Models\AuditLog;
use App\Models\BusinessHour;
use App\Models\SlaPolicy;
use App\Models\Team;
use App\Models\Ticket;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * SLA targets per team and priority plus the team's business hours (21.md).
 * An empty time field removes the policy / closes that weekday.
 */
#[Layout('layouts.admin')]
class SlaManager extends Component
{
    /** Carbon dayOfWeek order, Monday first for display. */
    public const DAYS = [1 => 'Mo', 2 => 'Di', 3 => 'Mi', 4 => 'Do', 5 => 'Fr', 6 => 'Sa', 0 => 'So'];

    public ?int $teamId = null;

    /** @var array<string, array{response: int|string|null, resolution: int|string|null}> */
    public array $policies = [];

    /** @var array<int, array{start: string|null, end: string|null}> */
    public array $hours = [];

    public function mount(): void
    {
        Gate::authorize('sla.manage');
        $this->selectTeam(Team::query()->orderBy('name')->value('id'));
    }

    public function selectTeam(?int $teamId): void
    {
        $this->teamId = $teamId;
        $policies = SlaPolicy::query()->where('team_id', $teamId)->get()->keyBy('priority');
        $hours = BusinessHour::query()->where('team_id', $teamId)->get()->keyBy('day_of_week');

        $this->policies = collect(Ticket::PRIORITIES)->mapWithKeys(fn (string $p) => [$p => [
            'response' => $policies[$p]->response_time_minutes ?? null,
            'resolution' => $policies[$p]->resolution_time_minutes ?? null,
        ]])->all();
        $this->hours = collect(array_keys(self::DAYS))->mapWithKeys(fn (int $d) => [$d => [
            'start' => isset($hours[$d]) ? substr($hours[$d]->start_time, 0, 5) : null,
            'end' => isset($hours[$d]) ? substr($hours[$d]->end_time, 0, 5) : null,
        ]])->all();
    }

    public function save(): void
    {
        Gate::authorize('sla.manage');
        $team = Team::query()->findOrFail($this->teamId);
        $this->validate([
            'policies.*.response' => ['nullable', 'integer', 'min:1', 'max:525600', 'required_with:policies.*.resolution'],
            'policies.*.resolution' => ['nullable', 'integer', 'min:1', 'max:525600', 'required_with:policies.*.response'],
            'hours.*.start' => ['nullable', 'date_format:H:i', 'required_with:hours.*.end'],
            'hours.*.end' => ['nullable', 'date_format:H:i', 'required_with:hours.*.start', 'after:hours.*.start'],
        ]);

        DB::transaction(fn () => $this->persist($team));
        AuditLog::record('sla.updated', Auth::user(), $team, null, ['policies' => $this->policies, 'hours' => $this->hours]);
        session()->flash('status', 'Gespeichert. Neue Fristen gelten für ab jetzt eingehende Tickets.');
    }

    public function render()
    {
        return view('livewire.admin.sla-manager', ['teams' => Team::query()->orderBy('name')->get()]);
    }

    private function persist(Team $team): void
    {
        foreach ($this->policies as $priority => $policy) {
            $policy['response']
                ? SlaPolicy::query()->updateOrCreate(['team_id' => $team->id, 'priority' => $priority], [
                    'name' => ucfirst($priority), 'response_time_minutes' => $policy['response'], 'resolution_time_minutes' => $policy['resolution'],
                ])
                : SlaPolicy::query()->where('team_id', $team->id)->where('priority', $priority)->delete();
        }

        foreach ($this->hours as $day => $window) {
            $window['start']
                ? BusinessHour::query()->updateOrCreate(['team_id' => $team->id, 'day_of_week' => $day], ['start_time' => $window['start'].':00', 'end_time' => $window['end'].':00'])
                : BusinessHour::query()->where('team_id', $team->id)->where('day_of_week', $day)->delete();
        }
    }
}
