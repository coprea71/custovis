<div class="space-y-6">
    <div>
        <h1 class="text-xl font-semibold text-slatecalm-900">Module</h1>
        <p class="text-sm text-slate-500">Deaktivierte Module verschwinden aus Navigation und Oberfläche. Ohne Zuordnung steht ein Modul allen offen; mit Rollen- oder Nutzerzuordnung nur diesen. Die Berechtigungen innerhalb eines Moduls gelten zusätzlich.</p>
    </div>

    <div class="bg-white border border-slatecalm-200 rounded-2xl divide-y divide-slatecalm-200">
        @foreach ($modules as $module)
            <div wire:key="mod-{{ $module->id }}" class="p-4 space-y-3">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <p class="font-medium text-slatecalm-900">{{ $module->name }}</p>
                        <p class="text-xs text-slate-500">{{ \App\Livewire\Admin\ModuleManager::DESCRIPTIONS[$module->slug] ?? $module->slug }}</p>
                        <p class="text-xs text-slate-400">
                            Zugang: {{ $module->roles->isEmpty() && $module->users->isEmpty() ? 'alle' : collect([$module->roles->pluck('name')->join(', '), $module->users->pluck('name')->join(', ')])->filter()->join(' · ') }}
                        </p>
                    </div>
                    <div class="flex gap-2 text-xs">
                        <button type="button" wire:click="edit({{ $module->id }})" class="px-3 py-1.5 rounded-lg bg-slatecalm-100 text-slate-700">Zugang</button>
                        <button type="button" wire:click="toggle({{ $module->id }})"
                                class="px-3 py-1.5 rounded-lg font-medium {{ $module->enabled ? 'bg-calm-100 text-calm-800' : 'bg-slatecalm-100 text-slate-500' }}">{{ $module->enabled ? 'Aktiv' : 'Inaktiv' }}</button>
                    </div>
                </div>

                @if ($editingId === $module->id)
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm">
                        <label>Rollen
                            <select wire:model="roleIds" multiple size="4" class="w-full mt-1 border border-slatecalm-200 rounded-xl px-2 py-1">
                                @foreach ($roles as $role)<option value="{{ $role->id }}">{{ $role->name }}</option>@endforeach
                            </select>
                        </label>
                        <label>Einzelne Nutzer
                            <select wire:model="userIds" multiple size="4" class="w-full mt-1 border border-slatecalm-200 rounded-xl px-2 py-1">
                                @foreach ($users as $user)<option value="{{ $user->id }}">{{ $user->name }}</option>@endforeach
                            </select>
                        </label>
                        <div class="md:col-span-2 flex justify-end">
                            <button type="button" wire:click="saveAssignments" class="px-4 py-2 bg-calm-600 hover:bg-calm-700 text-white rounded-xl font-medium">Zugang speichern</button>
                        </div>
                    </div>
                @endif
            </div>
        @endforeach
    </div>
</div>
