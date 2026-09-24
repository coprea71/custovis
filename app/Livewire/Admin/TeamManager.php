<?php

namespace App\Livewire\Admin;

use App\Models\AuditLog;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.admin')]
class TeamManager extends Component
{
    public const TEAM_ROLES = ['member' => 'Mitglied', 'team_admin' => 'Team-Admin'];

    public ?int $selectedId = null;

    public string $name = '';

    public string $description = '';

    public ?int $newMemberId = null;

    public string $newMemberRole = 'member';

    public function mount(): void
    {
        Gate::authorize('team.manage');
    }

    public function select(int $teamId): void
    {
        $team = Team::query()->findOrFail($teamId);
        $this->selectedId = $team->id;
        $this->name = $team->name;
        $this->description = (string) $team->description;
    }

    public function newTeam(): void
    {
        $this->reset(['selectedId', 'name', 'description']);
    }

    public function save(): void
    {
        Gate::authorize('team.manage');
        $data = $this->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('teams', 'name')->ignore($this->selectedId)],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        $team = $this->selectedId ? Team::query()->findOrFail($this->selectedId) : new Team(['slug' => $this->uniqueSlug($data['name'])]);
        $team->fill($data)->save();

        AuditLog::record($this->selectedId ? 'team.updated' : 'team.created', Auth::user(), $team, $team, ['name' => $team->name]);
        $this->select($team->id);
    }

    public function addMember(): void
    {
        Gate::authorize('team.manage');
        $this->validate([
            'newMemberId' => ['required', 'integer', 'exists:users,id'],
            'newMemberRole' => ['required', Rule::in(array_keys(self::TEAM_ROLES))],
        ]);

        $this->team()->users()->syncWithoutDetaching([$this->newMemberId => ['role_in_team' => $this->newMemberRole]]);
        AuditLog::record('team.member_added', Auth::user(), $this->team(), User::query()->find($this->newMemberId), ['role_in_team' => $this->newMemberRole]);
        $this->reset(['newMemberId', 'newMemberRole']);
    }

    public function changeRole(int $userId, string $role): void
    {
        Gate::authorize('team.manage');
        abort_unless(array_key_exists($role, self::TEAM_ROLES), 422);

        $this->team()->users()->updateExistingPivot($userId, ['role_in_team' => $role]);
        AuditLog::record('team.member_role_changed', Auth::user(), $this->team(), User::query()->find($userId), ['role_in_team' => $role]);
    }

    public function removeMember(int $userId): void
    {
        Gate::authorize('team.manage');

        $this->team()->users()->detach($userId);
        AuditLog::record('team.member_removed', Auth::user(), $this->team(), User::query()->find($userId));
    }

    public function render()
    {
        $team = $this->selectedId ? $this->team()->load(['users' => fn ($q) => $q->orderBy('name')]) : null;

        return view('livewire.admin.team-manager', [
            'teams' => Team::query()->withCount('users')->orderBy('name')->get(),
            'team' => $team,
            'candidates' => $team ? User::query()->where('active', true)->whereNotIn('id', $team->users->pluck('id'))->orderBy('name')->get() : collect(),
        ]);
    }

    private function team(): Team
    {
        return Team::query()->findOrFail($this->selectedId);
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'team';
        $slug = $base;

        for ($i = 2; Team::query()->where('slug', $slug)->exists(); $i++) {
            $slug = "{$base}-{$i}";
        }

        return $slug;
    }
}
