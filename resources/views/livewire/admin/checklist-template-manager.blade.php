<div class="space-y-6">
    <div class="flex items-center justify-between">
        <h1 class="text-xl font-semibold text-slatecalm-900">Checklisten-Vorlagen</h1>
        <a href="{{ route('admin.technicians') }}" class="text-sm text-slate-500 hover:text-calm-700">Zu den Technikern</a>
    </div>

    <div class="bg-white border border-slatecalm-200 rounded-2xl divide-y divide-slatecalm-200">
        @forelse ($templates as $template)
            <div wire:key="tpl-{{ $template->id }}" class="p-4 flex justify-between gap-4">
                <div>
                    <p class="font-medium text-slatecalm-900">{{ $template->name }}</p>
                    <p class="text-xs text-slate-500">{{ $template->items->pluck('label')->join(' · ') }}</p>
                </div>
                <button type="button" wire:click="delete({{ $template->id }})" wire:confirm="Vorlage löschen? Bereits kopierte Checklisten bleiben erhalten." class="text-xs text-red-600 shrink-0">Löschen</button>
            </div>
        @empty
            <p class="p-4 text-sm text-slate-400">Noch keine Vorlagen.</p>
        @endforelse
    </div>

    <form wire:submit="create" class="bg-white border border-slatecalm-200 rounded-2xl p-6 space-y-3">
        <h2 class="font-semibold text-slatecalm-900">Neue Vorlage</h2>
        <input type="text" wire:model="name" placeholder="Name, z. B. Heizungswartung" class="w-full border border-slatecalm-200 rounded-xl px-3 py-2 text-sm">
        @error('name') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
        <textarea wire:model="items" rows="5" placeholder="Ein Prüfpunkt pro Zeile" class="w-full border border-slatecalm-200 rounded-xl px-3 py-2 text-sm"></textarea>
        @error('items') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
        <div class="flex justify-end">
            <button type="submit" class="px-5 py-2 bg-calm-600 hover:bg-calm-700 text-white rounded-xl text-sm font-medium">Anlegen</button>
        </div>
    </form>
</div>
