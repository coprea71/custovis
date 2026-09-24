<div class="space-y-6">
    <div class="flex items-center justify-between">
        <h1 class="text-xl font-semibold text-slatecalm-900">CMDB — Configuration Items</h1>
        <button type="button" wire:click="newCi" class="px-4 py-2 bg-calm-600 hover:bg-calm-700 text-white rounded-xl text-sm font-medium">Neues CI</button>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <section class="bg-white border border-slatecalm-200 rounded-2xl p-3 space-y-1 max-h-[32rem] overflow-y-auto">
            @forelse ($items as $item)
                <button type="button" wire:key="ci-{{ $item->id }}" wire:click="edit({{ $item->id }})"
                        class="w-full text-left px-3 py-2 rounded-lg text-sm {{ $editingId === $item->id ? 'bg-calm-100 text-calm-800' : 'hover:bg-slatecalm-100' }}">
                    <span class="block">{{ $item->name }} @if ($item->status === 'retired') <span class="text-xs text-slate-400">(außer Betrieb)</span> @endif</span>
                    <span class="text-xs text-slate-400">{{ \App\Livewire\Admin\CmdbManager::TYPES[$item->type] ?? $item->type }} · {{ $item->team->name }}</span>
                </button>
            @empty
                <p class="p-2 text-sm text-slate-400">Noch keine CIs.</p>
            @endforelse
        </section>

        <section class="md:col-span-2 space-y-4">
            <form wire:submit="save" class="bg-white border border-slatecalm-200 rounded-2xl p-5 grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm">
                <h2 class="sm:col-span-2 font-semibold text-slatecalm-900">{{ $editingId ? 'CI bearbeiten' : 'CI anlegen' }}</h2>
                <label>Name<input type="text" wire:model="form.name" class="w-full mt-1 border border-slatecalm-200 rounded-xl px-3 py-2"></label>
                <label>Team
                    <select wire:model="form.team_id" class="w-full mt-1 border border-slatecalm-200 rounded-xl px-3 py-2">
                        <option value="">— Team wählen —</option>
                        @foreach ($teams as $team)<option value="{{ $team->id }}">{{ $team->name }}</option>@endforeach
                    </select>
                </label>
                <label>Typ
                    <select wire:model="form.type" class="w-full mt-1 border border-slatecalm-200 rounded-xl px-3 py-2">
                        @foreach (\App\Livewire\Admin\CmdbManager::TYPES as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach
                    </select>
                </label>
                <label>Status
                    <select wire:model="form.status" class="w-full mt-1 border border-slatecalm-200 rounded-xl px-3 py-2">
                        <option value="active">in Betrieb</option>
                        <option value="retired">außer Betrieb</option>
                    </select>
                </label>
                <p class="sm:col-span-2 text-xs text-red-600">{{ $errors->first('form.*') }}</p>
                <div class="sm:col-span-2 flex justify-end">
                    <button type="submit" class="px-5 py-2 bg-calm-600 hover:bg-calm-700 text-white rounded-xl font-medium">Speichern</button>
                </div>
            </form>

            @if ($editingId)
                <div class="bg-white border border-slatecalm-200 rounded-2xl p-5 space-y-2 text-sm">
                    <h2 class="font-semibold text-slatecalm-900">Beziehungen</h2>
                    @forelse ($relations as $rel)
                        <p class="flex justify-between">{{ \App\Livewire\Admin\CmdbManager::RELATIONS[$rel->relation_type] ?? $rel->relation_type }} <strong>{{ $rel->target->name }}</strong>
                            <button type="button" wire:click="removeRelation({{ $rel->id }})" class="text-xs text-red-600">Entfernen</button></p>
                    @empty
                        <p class="text-slate-400">Keine Beziehungen.</p>
                    @endforelse
                    <div class="flex flex-wrap gap-2 pt-2 border-t border-slatecalm-200">
                        <select wire:model="relation.type" aria-label="Beziehungstyp" class="border border-slatecalm-200 rounded-xl px-2 py-1.5">
                            @foreach (\App\Livewire\Admin\CmdbManager::RELATIONS as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach
                        </select>
                        <select wire:model="relation.target" aria-label="Ziel-CI" class="flex-1 border border-slatecalm-200 rounded-xl px-2 py-1.5">
                            <option value="">CI wählen...</option>
                            @foreach ($items->where('id', '!=', $editingId) as $item)<option value="{{ $item->id }}">{{ $item->name }}</option>@endforeach
                        </select>
                        <button type="button" wire:click="addRelation" class="px-3 py-1.5 bg-slatecalm-100 rounded-xl">Hinzufügen</button>
                    </div>
                    @error('relation.target') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
            @endif
        </section>
    </div>
</div>
