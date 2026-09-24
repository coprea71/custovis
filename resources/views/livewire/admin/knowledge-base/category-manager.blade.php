<div class="space-y-6">
    <h1 class="text-xl font-semibold text-slatecalm-900">Wissensdatenbank — Kategorien</h1>

    @error('delete') <p class="text-sm text-red-600">{{ $message }}</p> @enderror

    <div class="bg-white border border-slatecalm-200 rounded-2xl divide-y divide-slatecalm-200">
        @forelse ($categories as $category)
            <div wire:key="cat-{{ $category->id }}" class="p-3 flex items-center justify-between" style="padding-left: {{ 1 + $category->depth * 1.5 }}rem">
                <span class="text-sm text-slatecalm-900">{{ $category->name }}</span>
                <button wire:click="delete({{ $category->id }})" wire:confirm="Kategorie löschen?" class="text-xs text-slate-400 hover:text-red-600">Löschen</button>
            </div>
        @empty
            <p class="p-4 text-sm text-slate-400">Noch keine Kategorie angelegt.</p>
        @endforelse
    </div>

    <div class="bg-white border border-slatecalm-200 rounded-2xl p-6">
        <h2 class="font-semibold text-slatecalm-900 mb-4">Neue Kategorie</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="text-xs font-medium text-slate-600">Name</label>
                <input type="text" wire:model="name" class="w-full mt-1 border border-slatecalm-200 rounded-xl px-3 py-2 text-sm">
                @error('name') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="text-xs font-medium text-slate-600">Übergeordnete Kategorie</label>
                <select wire:model="parent_id" class="w-full mt-1 border border-slatecalm-200 rounded-xl px-3 py-2 text-sm">
                    <option value="">— keine (oberste Ebene) —</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}">{{ str_repeat('— ', $category->depth) }}{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="flex justify-end mt-4">
            <button wire:click="create" class="px-5 py-2 bg-calm-600 hover:bg-calm-700 text-white rounded-xl text-sm font-medium">Anlegen</button>
        </div>
    </div>
</div>
