<?php

namespace App\Livewire\Agent\Team;

use App\Models\AuditLog;
use App\Models\GitIssueConnection;
use App\Models\Team;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.agent')]
class GitIssueConnectionManager extends Component
{
    public Team $team;

    public bool $readOnly = false;

    public string $provider = 'github';

    public string $repository = '';

    public string $accessToken = '';

    public string $webhookSecret = '';

    public string $syncMode = 'webhook';

    public function mount(Team $team): void
    {
        $user = Auth::user();

        if ($user->isTeamAdminOf($team)) {
            $this->readOnly = false;
        } elseif ($user->can('team.git_issues.manage')) {
            $this->readOnly = true;
        } else {
            abort(403);
        }

        $this->team = $team;
    }

    public function createConnection(): void
    {
        abort_if($this->readOnly, 403);

        $data = $this->validate([
            'provider' => ['required', 'in:github,gitlab'],
            'repository' => ['required', 'string', 'max:255'],
            'accessToken' => ['required', 'string'],
            'webhookSecret' => ['required', 'string', 'min:8'],
            'syncMode' => ['required', 'in:webhook,poll'],
        ]);

        $connection = GitIssueConnection::query()->create([
            'team_id' => $this->team->id,
            'provider' => $data['provider'],
            'repository' => $data['repository'],
            'access_token' => $data['accessToken'],
            'webhook_secret' => $data['webhookSecret'],
            'sync_mode' => $data['syncMode'],
            'created_by' => Auth::id(),
        ]);

        AuditLog::record('git_issue_connection.created', Auth::user(), $this->team, $connection, [
            'provider' => $connection->provider,
            'repository' => $connection->repository,
        ]);

        $this->reset(['repository', 'accessToken', 'webhookSecret']);
        $this->syncMode = 'webhook';
    }

    public function revokeConnection(int $connectionId): void
    {
        abort_if($this->readOnly, 403);

        $connection = GitIssueConnection::query()->where('team_id', $this->team->id)->findOrFail($connectionId);
        $connection->update(['revoked_at' => now()]);

        AuditLog::record('git_issue_connection.revoked', Auth::user(), $this->team, $connection, [
            'provider' => $connection->provider,
            'repository' => $connection->repository,
        ]);
    }

    public function render()
    {
        return view('livewire.agent.team.git-issue-connection-manager', [
            'connections' => GitIssueConnection::query()->where('team_id', $this->team->id)->with('creator')->latest()->get(),
        ]);
    }
}
