<?php

namespace App\Livewire\Agent\Team;

use App\Models\ApiClient;
use App\Models\AuditLog;
use App\Models\Team;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.agent')]
class ApiKeyManager extends Component
{
    public Team $team;

    public bool $readOnly = false;

    public string $newClientName = '';

    public ?string $plainTextToken = null;

    public function mount(Team $team): void
    {
        $user = Auth::user();

        if ($user->isTeamAdminOf($team)) {
            $this->readOnly = false;
        } elseif ($user->can('team.api_keys.manage')) {
            $this->readOnly = true; // system admin: audit view only
        } else {
            abort(403);
        }

        $this->team = $team;
    }

    public function createClient(): void
    {
        abort_if($this->readOnly, 403);

        $this->validate(['newClientName' => ['required', 'string', 'max:255']]);

        $client = ApiClient::query()->create([
            'team_id' => $this->team->id,
            'name' => $this->newClientName,
            'created_by' => Auth::id(),
        ]);

        $token = $client->createToken($client->name, ['tickets.create']);

        AuditLog::record('api_client.created', Auth::user(), $this->team, $client, ['name' => $client->name]);

        $this->plainTextToken = $token->plainTextToken;
        $this->newClientName = '';
    }

    public function revokeClient(int $clientId): void
    {
        abort_if($this->readOnly, 403);

        $client = ApiClient::query()->where('team_id', $this->team->id)->findOrFail($clientId);
        $client->revoke();

        AuditLog::record('api_client.revoked', Auth::user(), $this->team, $client, ['name' => $client->name]);
    }

    public function dismissToken(): void
    {
        $this->plainTextToken = null;
    }

    public function render()
    {
        $clients = ApiClient::query()->where('team_id', $this->team->id)->with('creator', 'tokens')->latest()->get();

        return view('livewire.agent.team.api-key-manager', ['clients' => $clients]);
    }
}
