<div class="flex-1 overflow-y-auto p-6 max-w-3xl space-y-6">
    <div>
        <h1 class="text-xl font-semibold text-slatecalm-900">Textbausteine — {{ $team->name }}</h1>
        <p class="text-sm text-slate-500">{{ $readOnly ? 'Nur-Lese-Ansicht.' : 'Stehen allen Agenten des Teams im Antwortfeld zur Verfügung.' }}</p>
    </div>

    <div class="bg-white border border-slatecalm-200 rounded-2xl divide-y divide-slatecalm-200">
        @forelse ($responses as $response)
            <div wire:key="cr-{{ $response->id }}" class="p-4 flex justify-between gap-4">
                <div class="min-w-0">
                    <p class="font-medium text-slatecalm-900">{{ $response->title }}</p>
                    <p class="text-sm text-slate-500 whitespace-pre-line line-clamp-3">{{ $response->body }}</p>
                </div>
                @unless ($readOnly)
                    <div class="flex gap-2 shrink-0 text-xs">
                        <button type="button" wire:click="edit({{ $response->id }})" class="px-3 py-1.5 rounded-lg bg-slatecalm-100 text-slate-700">Bearbeiten</button>
                        <button type="button" wire:click="delete({{ $response->id }})" wire:confirm="Textbaustein löschen?" class="px-3 py-1.5 rounded-lg bg-red-50 text-red-700">Löschen</button>
                    </div>
                @endunless
            </div>
        @empty
            <p class="p-4 text-sm text-slate-400">Noch keine Textbausteine.</p>
        @endforelse
    </div>

    @unless ($readOnly)
        <form wire:submit="save" class="bg-white border border-slatecalm-200 rounded-2xl p-6 space-y-3 text-sm">
            <h2 class="font-semibold text-slatecalm-900">{{ $editingId ? 'Textbaustein bearbeiten' : 'Neuer Textbaustein' }}</h2>
            <label class="block">Titel<input type="text" wire:model="title" class="w-full mt-1 border border-slatecalm-200 rounded-xl px-3 py-2"></label>
            @error('title') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
            <label class="block">Text<textarea wire:model="body" rows="5" class="w-full mt-1 border border-slatecalm-200 rounded-xl px-3 py-2"></textarea></label>
            @error('body') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
            <div class="flex justify-end">
                <button type="submit" class="px-5 py-2 bg-calm-600 hover:bg-calm-700 text-white rounded-xl font-medium">Speichern</button>
            </div>
        </form>
    @endunless
</div>
