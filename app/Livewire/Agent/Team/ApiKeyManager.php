<?php

namespace App\Livewire\Agent\Team;

use App\Models\ApiClient;
use App\Models\AuditLog;
use App\Models\KnowledgeBaseCategory;
use App\Models\Mailbox;
use App\Models\Team;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.agent')]
class ApiKeyManager extends Component
{
    public const ABILITY_REST = 'tickets.create';

    // Granted together with ABILITY_REST: a client may read back the tickets it can create.
    public const ABILITY_REST_VIEW = 'tickets.view';

    public const ABILITY_MCP = 'mcp.tools.use';

    public Team $team;

    public bool $readOnly = false;

    public string $newClientName = '';

    public bool $allowRest = true;

    public bool $allowMcp = false;

    public ?int $mailboxId = null;

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
        $this->mailboxId = $this->mailboxes()->first()?->id;
    }

    public function createClient(): void
    {
        abort_if($this->readOnly, 403);

        $this->validate([
            'newClientName' => ['required', 'string', 'max:255'],
            'allowRest' => ['boolean'],
            'allowMcp' => ['boolean'],
            'kbCategoryIds.*' => ['integer', 'exists:knowledge_base_categories,id'],
            'mailboxId' => ['nullable', 'integer', Rule::in($this->mailboxes()->modelKeys())],
        ]);

        if (! $this->allowRest && ! $this->allowMcp) {
            $this->addError('allowRest', 'Mindestens REST-API oder MCP muss freigeschaltet sein.');

            return;
        }

        $client = ApiClient::query()->create([
            'team_id' => $this->team->id,
            'name' => $this->newClientName,
            'mailbox_id' => $this->mailboxId,
            'created_by' => Auth::id(),
        ]);

        $abilities = array_keys(array_filter([
            self::ABILITY_REST => $this->allowRest,
            self::ABILITY_REST_VIEW => $this->allowRest,
            self::ABILITY_MCP => $this->allowMcp,
        ]));
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

    public function saveMailbox(int $clientId, string $mailboxId): void
    {
        abort_if($this->readOnly, 403);

        $mailboxId = $mailboxId === '' ? null : (int) $mailboxId;
        abort_unless($mailboxId === null || in_array($mailboxId, $this->mailboxes()->modelKeys(), true), 422);

        $client = $this->teamClient($clientId);
        $client->update(['mailbox_id' => $mailboxId]);

        AuditLog::record('api_client.mailbox_changed', Auth::user(), $this->team, $client, ['mailbox_id' => $mailboxId]);
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
        $clients = ApiClient::query()->where('team_id', $this->team->id)->with('creator', 'tokens', 'kbCategories', 'mailbox')->latest()->get();

        return view('livewire.agent.team.api-key-manager', [
            'clients' => $clients,
            'categories' => KnowledgeBaseCategory::tree(),
            'mailboxes' => $this->mailboxes(),
        ]);
    }

    /**
     * Active mailboxes of this team, the only allowed senders for API tickets.
     *
     * @return Collection<int, Mailbox>
     */
    private function mailboxes(): Collection
    {
        return Mailbox::query()->where('team_id', $this->team->id)->where('active', true)->orderBy('id')->get();
    }

    private function teamClient(int $clientId): ApiClient
    {
        return ApiClient::query()->where('team_id', $this->team->id)->findOrFail($clientId);
    }
}
