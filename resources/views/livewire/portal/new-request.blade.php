<div class="space-y-4">
    <h1 class="text-xl font-semibold text-slatecalm-900">Neue Anfrage</h1>

    <form wire:submit="submit" class="bg-white border border-slatecalm-200 rounded-2xl p-6 space-y-4">
        <fieldset>
            <legend class="text-sm font-medium text-slatecalm-900 mb-2">Worum geht es?</legend>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                @forelse ($items as $item)
                    <label wire:key="item-{{ $item->id }}" class="border rounded-xl p-3 cursor-pointer {{ $service_catalog_item_id === $item->id ? 'border-calm-500 bg-calm-50' : 'border-slatecalm-200' }}">
                        <input type="radio" wire:model.live="service_catalog_item_id" value="{{ $item->id }}" class="sr-only">
                        <span class="block text-sm font-medium text-slatecalm-900">{{ $item->name }}</span>
                        <span class="block text-xs text-slate-500">{{ $item->description }}</span>
                    </label>
                @empty
                    <p class="text-sm text-slate-400">Derzeit sind keine Leistungen im Service-Katalog freigeschaltet.</p>
                @endforelse
            </div>
            @error('service_catalog_item_id') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
        </fieldset>

        <div>
            <label for="description" class="text-sm font-medium text-slatecalm-900">Beschreibung</label>
            <textarea id="description" wire:model="description" rows="5" class="w-full mt-1 border border-slatecalm-200 rounded-xl px-3 py-2 text-sm"></textarea>
            @error('description') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
        </div>

        <div class="flex justify-end">
            <button type="submit" class="px-5 py-2 bg-calm-600 hover:bg-calm-700 text-white rounded-xl text-sm font-medium">Anfrage absenden</button>
        </div>
    </form>
</div>
