<?php

namespace App\Livewire\Agent\Team;

use App\Models\CannedResponse;
use App\Models\Team;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Team-owned reply templates (20.md): team admins edit, holders of
 * team.manage see them read-only — same pattern as the other team settings.
 */
#[Layout('layouts.agent')]
class CannedResponseManager extends Component
{
    #[Locked]
    public Team $team;

    #[Locked]
    public bool $readOnly = true;

    public ?int $editingId = null;

    public string $title = '';

    public string $body = '';

    public function mount(Team $team): void
    {
        $user = Auth::user();
        abort_unless($user->isTeamAdminOf($team) || $user->can('team.manage'), 403);

        $this->team = $team;
        $this->readOnly = ! $user->isTeamAdminOf($team);
    }

    public function edit(int $id): void
    {
        $response = $this->response($id);
        $this->editingId = $response->id;
        $this->title = $response->title;
        $this->body = $response->body;
    }

    public function save(): void
    {
        abort_if($this->readOnly, 403);
        $data = $this->validate(['title' => ['required', 'string', 'max:255'], 'body' => ['required', 'string', 'max:10000']]);

        $this->editingId
            ? $this->response($this->editingId)->update($data)
            : CannedResponse::query()->create($data + ['team_id' => $this->team->id]);

        $this->reset(['editingId', 'title', 'body']);
    }

    public function delete(int $id): void
    {
        abort_if($this->readOnly, 403);
        $this->response($id)->delete();
    }

    public function render()
    {
        return view('livewire.agent.team.canned-response-manager', [
            'responses' => CannedResponse::query()->where('team_id', $this->team->id)->orderBy('title')->get(),
        ]);
    }

    private function response(int $id): CannedResponse
    {
        return CannedResponse::query()->where('team_id', $this->team->id)->findOrFail($id);
    }
}
