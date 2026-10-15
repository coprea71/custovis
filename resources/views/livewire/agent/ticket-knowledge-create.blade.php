<div class="flex items-center gap-2">
    @if ($status)
        <span class="text-xs text-calm-700" role="status">{{ $status }}</span>
    @endif
    <button type="button" wire:click="openModal" class="text-xs font-medium text-calm-700 hover:underline">+ Artikel</button>

    @if ($open)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 p-4" wire:keydown.escape.window="closeModal">
            <div class="w-full max-w-lg max-h-full overflow-y-auto bg-white rounded-2xl shadow-xl p-6 text-left" role="dialog" aria-modal="true" aria-labelledby="kb-create-title">
                <h2 id="kb-create-title" class="font-semibold text-slatecalm-900 mb-4">Wissensartikel anlegen</h2>

                <form wire:submit="save" class="space-y-3">
                    <div>
                        <label for="kb-create-title-input" class="text-xs font-medium text-slate-600">Titel</label>
                        <input id="kb-create-title-input" type="text" wire:model="title" maxlength="255" class="w-full mt-1 border border-slatecalm-200 rounded-xl px-3 py-2 text-sm">
                        @error('title') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="kb-create-category" class="text-xs font-medium text-slate-600">Kategorie</label>
                        <select id="kb-create-category" wire:model="category_id" class="w-full mt-1 border border-slatecalm-200 rounded-xl px-3 py-2 text-sm">
                            <option value="">Bitte wählen</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}">{{ str_repeat('— ', $category->depth) }}{{ $category->name }}</option>
                            @endforeach
                        </select>
                        @error('category_id') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="kb-create-body" class="text-xs font-medium text-slate-600">Inhalt</label>
                        <textarea id="kb-create-body" wire:model="body" rows="8" class="w-full mt-1 border border-slatecalm-200 rounded-xl px-3 py-2 text-sm"></textarea>
                        @error('body') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>
                    @can('kb.articles.manage')
                        <div>
                            <label for="kb-create-visibility" class="text-xs font-medium text-slate-600">Sichtbarkeit</label>
                            <select id="kb-create-visibility" wire:model="visibility" class="w-full mt-1 border border-slatecalm-200 rounded-xl px-3 py-2 text-sm">
                                <option value="internal">Intern</option>
                                <option value="public">Öffentlich (Kundenportal)</option>
                            </select>
                            @error('visibility') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                        </div>
                    @else
                        <p class="text-xs text-slate-500">Der Artikel wird intern angelegt.</p>
                    @endcan

                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" wire:click="closeModal" class="text-sm px-4 py-2 rounded-xl bg-slatecalm-100 text-slate-700">Abbrechen</button>
                        <button type="submit" class="text-sm px-5 py-2 rounded-xl bg-calm-600 hover:bg-calm-700 text-white font-medium">Speichern</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
