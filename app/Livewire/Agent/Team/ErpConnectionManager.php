<?php

namespace App\Livewire\Agent\Team;

use App\Models\AuditLog;
use App\Models\ErpConnection;
use App\Models\Team;
use App\Rules\SafeExternalUrl;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

#[Layout('layouts.agent')]
class ErpConnectionManager extends Component
{
    private const MAX_MAPPED_FIELDS = 20;

    public Team $team;

    #[Locked]
    public bool $readOnly = false;

    #[Locked]
    public ?int $editingId = null;

    public string $name = '';

    public string $type = ErpConnection::TYPE_ODOO;

    public string $baseUrl = '';

    public string $fieldMappingText = "name=Name\nemail=E-Mail";

    public string $database = '';

    public string $login = '';

    public string $clientId = '';

    public string $secret = '';

    public function mount(Team $team): void
    {
        $user = Auth::user();

        if ($user->isTeamAdminOf($team)) {
            $this->readOnly = false;
        } elseif ($user->can('team.erp.manage')) {
            $this->readOnly = true;
        } else {
            abort(403);
        }

        $this->team = $team;
    }

    public function save(): void
    {
        abort_if($this->readOnly, 403);

        $this->editingId ? $this->updateConnection() : $this->createConnection();
    }

    public function edit(int $connectionId): void
    {
        abort_if($this->readOnly, 403);

        $connection = $this->findConnection($connectionId);
        $this->resetForm();
        $this->editingId = $connection->id;
        $this->name = $connection->name;
        $this->type = $connection->type;
        $this->baseUrl = $connection->base_url;
        $this->fieldMappingText = collect($connection->field_mapping)
            ->map(fn (string $label, string $field) => "{$field}={$label}")
            ->implode("\n");
    }

    public function cancelEdit(): void
    {
        $this->resetForm();
    }

    public function toggleActive(int $connectionId): void
    {
        abort_if($this->readOnly, 403);

        $connection = $this->findConnection($connectionId);
        $connection->update(['is_active' => ! $connection->is_active]);

        AuditLog::record($connection->is_active ? 'erp_connection.activated' : 'erp_connection.deactivated', Auth::user(), $this->team, $connection, [
            'name' => $connection->name,
            'type' => $connection->type,
        ]);
    }

    public function render()
    {
        return view('livewire.agent.team.erp-connection-manager', [
            'connections' => ErpConnection::query()->where('team_id', $this->team->id)->orderBy('name')->get(),
        ]);
    }

    private function createConnection(): void
    {
        $this->validate($this->rules(true));

        $connection = ErpConnection::query()->create([
            'team_id' => $this->team->id,
            'name' => $this->name,
            'type' => $this->type,
            'base_url' => $this->baseUrl,
            'auth_payload' => $this->credentialsPayload(),
            'field_mapping' => $this->parseFieldMapping(),
            'is_active' => true,
            'created_by' => Auth::id(),
        ]);

        AuditLog::record('erp_connection.created', Auth::user(), $this->team, $connection, [
            'name' => $connection->name,
            'type' => $connection->type,
            'base_url' => $connection->base_url,
        ]);

        $this->resetForm();
    }

    private function updateConnection(): void
    {
        $connection = $this->findConnection($this->editingId);
        $this->type = $connection->type;
        $baseUrlChanged = $this->baseUrl !== $connection->base_url;
        // A new target URL must come with fresh credentials, otherwise stored secrets could be sent to another host.
        $rotate = $baseUrlChanged || $this->secret !== '';

        $this->validate($this->rules($rotate));

        $connection->fill([
            'name' => $this->name,
            'base_url' => $this->baseUrl,
            'field_mapping' => $this->parseFieldMapping(),
        ]);

        if ($rotate) {
            $connection->auth_payload = $this->credentialsPayload();
        }

        $connection->save();

        AuditLog::record('erp_connection.updated', Auth::user(), $this->team, $connection, [
            'name' => $connection->name,
            'base_url_changed' => $baseUrlChanged,
            'credentials_rotated' => $rotate,
        ]);

        $this->resetForm();
    }

    private function rules(bool $withCredentials): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:100'],
            'type' => ['required', Rule::in(ErpConnection::TYPES)],
            'baseUrl' => ['required', 'string', 'max:255', new SafeExternalUrl],
            'fieldMappingText' => ['required', 'string', 'max:2000'],
        ];

        if (! $withCredentials) {
            return $rules;
        }

        $credentialRules = $this->type === ErpConnection::TYPE_ODOO
            ? ['database' => ['required', 'string', 'max:100'], 'login' => ['required', 'string', 'max:255']]
            : ['clientId' => ['required', 'string', 'max:255']];

        return $rules + $credentialRules + ['secret' => ['required', 'string', 'max:500']];
    }

    /**
     * @return array<string, string>
     */
    private function credentialsPayload(): array
    {
        return $this->type === ErpConnection::TYPE_ODOO
            ? ['database' => $this->database, 'login' => $this->login, 'api_key' => $this->secret]
            : ['client_id' => $this->clientId, 'client_secret' => $this->secret];
    }

    /**
     * Parses "remote_field=Label" lines. Field names are whitelisted by
     * pattern because they are sent to the ERP as requested columns.
     *
     * @return array<string, string>
     */
    private function parseFieldMapping(): array
    {
        $mapping = [];

        foreach (preg_split('/\R/', trim($this->fieldMappingText)) as $line) {
            [$field, $label] = array_pad(array_map('trim', explode('=', $line, 2)), 2, '');

            if (! preg_match('/^[A-Za-z][A-Za-z0-9_]{0,63}$/', $field) || $label === '' || mb_strlen($label) > 64) {
                throw ValidationException::withMessages(['fieldMappingText' => "Ungültige Zeile: \"{$line}\" (Format: feldname=Bezeichnung)."]);
            }

            $mapping[$field] = $label;
        }

        if (count($mapping) > self::MAX_MAPPED_FIELDS) {
            throw ValidationException::withMessages(['fieldMappingText' => 'Maximal '.self::MAX_MAPPED_FIELDS.' Felder erlaubt.']);
        }

        return $mapping;
    }

    private function findConnection(?int $connectionId): ErpConnection
    {
        return ErpConnection::query()->where('team_id', $this->team->id)->findOrFail($connectionId);
    }

    private function resetForm(): void
    {
        $this->reset(['editingId', 'name', 'type', 'baseUrl', 'fieldMappingText', 'database', 'login', 'clientId', 'secret']);
        $this->resetValidation();
    }
}
