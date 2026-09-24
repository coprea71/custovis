<?php

namespace App\Livewire\Admin;

use App\Models\AuditLog;
use App\Models\CmdbCiRelation;
use App\Models\CmdbConfigurationItem;
use App\Models\Team;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.admin')]
class CmdbManager extends Component
{
    public const TYPES = ['server' => 'Server', 'application' => 'Anwendung', 'network_device' => 'Netzwerkgerät', 'workstation' => 'Arbeitsplatz', 'service' => 'Dienst'];

    public const RELATIONS = ['depends_on' => 'hängt ab von', 'hosts' => 'hostet', 'connects_to' => 'verbunden mit'];

    public ?int $editingId = null;

    /** @var array{team_id: int|string, name: string, type: string, status: string} */
    public array $form = ['team_id' => '', 'name' => '', 'type' => 'server', 'status' => 'active'];

    /** @var array{target: int|string, type: string} */
    public array $relation = ['target' => '', 'type' => 'depends_on'];

    public function mount(): void
    {
        Gate::authorize('cmdb.manage');
    }

    public function edit(int $id): void
    {
        $ci = CmdbConfigurationItem::query()->findOrFail($id);
        $this->editingId = $ci->id;
        $this->form = $ci->only(['team_id', 'name', 'type', 'status']);
    }

    public function newCi(): void
    {
        $this->reset(['editingId', 'form']);
    }

    public function save(): void
    {
        Gate::authorize('cmdb.manage');
        $data = $this->validate([
            'form.team_id' => ['required', 'integer', 'exists:teams,id'],
            'form.name' => ['required', 'string', 'max:255'],
            'form.type' => ['required', Rule::in(array_keys(self::TYPES))],
            'form.status' => ['required', Rule::in(['active', 'retired'])],
        ])['form'];

        $ci = $this->editingId
            ? tap(CmdbConfigurationItem::query()->findOrFail($this->editingId))->update($data)
            : CmdbConfigurationItem::query()->create($data);
        AuditLog::record($this->editingId ? 'cmdb_ci.updated' : 'cmdb_ci.created', Auth::user(), $ci->team, $ci, ['name' => $ci->name]);
        $this->edit($ci->id);
    }

    public function addRelation(): void
    {
        Gate::authorize('cmdb.manage');
        $this->validate([
            'relation.target' => ['required', 'integer', 'exists:cmdb_configuration_items,id', Rule::notIn([$this->editingId])],
            'relation.type' => ['required', Rule::in(array_keys(self::RELATIONS))],
        ]);

        CmdbCiRelation::query()->firstOrCreate(['source_ci_id' => $this->editingId, 'target_ci_id' => $this->relation['target'], 'relation_type' => $this->relation['type']]);
        $this->reset('relation');
    }

    public function removeRelation(int $relationId): void
    {
        Gate::authorize('cmdb.manage');
        CmdbCiRelation::query()->where('source_ci_id', $this->editingId)->findOrFail($relationId)->delete();
    }

    public function render()
    {
        return view('livewire.admin.cmdb-manager', [
            'items' => CmdbConfigurationItem::query()->with('team')->orderBy('name')->get(),
            'teams' => Team::query()->orderBy('name')->get(),
            'relations' => $this->editingId ? CmdbCiRelation::query()->where('source_ci_id', $this->editingId)->with('target')->get() : collect(),
        ]);
    }
}
