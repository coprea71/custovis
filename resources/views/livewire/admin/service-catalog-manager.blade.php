<div class="space-y-6">
    <h1 class="text-xl font-semibold text-slatecalm-900">Service-Katalog</h1>

    <div class="bg-white border border-slatecalm-200 rounded-2xl divide-y divide-slatecalm-200">
        @forelse ($items as $item)
            <div class="p-4 flex items-center justify-between">
                <div>
                    <p class="font-medium text-slatecalm-900">{{ $item->name }} <span class="text-xs text-slate-400">({{ $item->team->name }})</span></p>
                    <p class="text-sm text-slate-500">{{ $item->description }}</p>
                </div>
                <button wire:click="toggleActive({{ $item->id }})" class="text-xs px-3 py-1.5 rounded-lg font-medium {{ $item->active ? 'bg-calm-100 text-calm-800' : 'bg-slatecalm-100 text-slate-500' }}">
                    {{ $item->active ? 'Aktiv' : 'Inaktiv' }}
                </button>
            </div>
        @empty
            <p class="p-4 text-sm text-slate-400">Noch kein Eintrag angelegt.</p>
        @endforelse
    </div>

    <div class="bg-white border border-slatecalm-200 rounded-2xl p-6">
        <h2 class="font-semibold text-slatecalm-900 mb-4">Neuer Eintrag</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="text-xs font-medium text-slate-600">Team</label>
                <select wire:model="team_id" class="w-full mt-1 border border-slatecalm-200 rounded-xl px-3 py-2 text-sm">
                    <option value="0">— Team wählen —</option>
                    @foreach ($teams as $team)
                        <option value="{{ $team->id }}">{{ $team->name }}</option>
                    @endforeach
                </select>
                @error('team_id') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="text-xs font-medium text-slate-600">Name</label>
                <input type="text" wire:model="name" class="w-full mt-1 border border-slatecalm-200 rounded-xl px-3 py-2 text-sm">
                @error('name') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>
            <div class="md:col-span-2">
                <label class="text-xs font-medium text-slate-600">Beschreibung</label>
                <textarea wire:model="description" rows="2" class="w-full mt-1 border border-slatecalm-200 rounded-xl px-3 py-2 text-sm"></textarea>
            </div>
        </div>
        <div class="flex justify-end mt-4">
            <button wire:click="create" class="px-5 py-2 bg-calm-600 hover:bg-calm-700 text-white rounded-xl text-sm font-medium">Anlegen</button>
        </div>
    </div>
</div>
