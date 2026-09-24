<?php

namespace App\Livewire\Agent\Team;

use App\Models\ApiClient;
use App\Models\AuditLog;
use App\Models\KnowledgeBaseCategory;
use App\Models\Team;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.agent')]
class ApiKeyManager extends Component
{
    public const ABILITY_REST = 'tickets.create';

    public const ABILITY_MCP = 'mcp.tools.use';

    public Team $team;

    public bool $readOnly = false;

    public string $newClientName = '';

    public bool $allowRest = true;

    public bool $allowMcp = false;

    /** @var array<int, int|string> */
    public array $kbCategoryIds = [];

    public ?int $editingClientId = null;

    /** @var array<int, int|string> */
    public array $editKbCategoryIds = [];

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

        $this->validate([
            'newClientName' => ['required', 'string', 'max:255'],
            'allowRest' => ['boolean'],
            'allowMcp' => ['boolean'],
            'kbCategoryIds.*' => ['integer', 'exists:knowledge_base_categories,id'],
        ]);

        if (! $this->allowRest && ! $this->allowMcp) {
            $this->addError('allowRest', 'Mindestens REST-API oder MCP muss freigeschaltet sein.');

            return;
        }

        $client = ApiClient::query()->create([
            'team_id' => $this->team->id,
            'name' => $this->newClientName,
            'created_by' => Auth::id(),
        ]);

        $abilities = array_keys(array_filter([self::ABILITY_REST => $this->allowRest, self::ABILITY_MCP => $this->allowMcp]));
        $token = $client->createToken($client->name, $abilities);
        $client->kbCategories()->sync($this->allowMcp ? $this->kbCategoryIds : []);

        AuditLog::record('api_client.created', Auth::user(), $this->team, $client, ['name' => $client->name, 'abilities' => $abilities]);

        $this->plainTextToken = $token->plainTextToken;
        $this->reset(['newClientName', 'allowMcp', 'kbCategoryIds']);
    }

    public function editKbCategories(int $clientId): void
    {
        $client = $this->teamClient($clientId);
        $this->editingClientId = $client->id;
        $this->editKbCategoryIds = $client->kbCategories()->pluck('knowledge_base_categories.id')->all();
    }

    public function saveKbCategories(): void
    {
        abort_if($this->readOnly || ! $this->editingClientId, 403);

        $this->validate(['editKbCategoryIds.*' => ['integer', 'exists:knowledge_base_categories,id']]);

        $client = $this->teamClient($this->editingClientId);
        $client->kbCategories()->sync($this->editKbCategoryIds);

        AuditLog::record('api_client.kb_scope_changed', Auth::user(), $this->team, $client, ['category_ids' => array_map('intval', $this->editKbCategoryIds)]);

        $this->reset(['editingClientId', 'editKbCategoryIds']);
    }

    public function revokeClient(int $clientId): void
    {
        abort_if($this->readOnly, 403);

        $client = $this->teamClient($clientId);
        $client->revoke();

        AuditLog::record('api_client.revoked', Auth::user(), $this->team, $client, ['name' => $client->name]);
    }

    public function dismissToken(): void
    {
        $this->plainTextToken = null;
    }

    public function render()
    {
        $clients = ApiClient::query()->where('team_id', $this->team->id)->with('creator', 'tokens', 'kbCategories')->latest()->get();

        return view('livewire.agent.team.api-key-manager', [
            'clients' => $clients,
            'categories' => KnowledgeBaseCategory::tree(),
        ]);
    }

    private function teamClient(int $clientId): ApiClient
    {
        return ApiClient::query()->where('team_id', $this->team->id)->findOrFail($clientId);
    }
}
