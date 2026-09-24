<div class="space-y-6">
    <div class="flex items-center justify-between">
        <h1 class="text-xl font-semibold text-slatecalm-900">{{ $article ? 'Artikel bearbeiten' : 'Neuer Artikel' }}</h1>
        <a href="{{ route('admin.kb.articles.index') }}" class="text-sm text-slate-500 hover:text-calm-700">Zur Übersicht</a>
    </div>

    <div class="bg-white border border-slatecalm-200 rounded-2xl p-6 space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="md:col-span-2">
                <label class="text-xs font-medium text-slate-600">Titel</label>
                <input type="text" wire:model="title" class="w-full mt-1 border border-slatecalm-200 rounded-xl px-3 py-2 text-sm">
                @error('title') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="text-xs font-medium text-slate-600">Sichtbarkeit</label>
                <select wire:model="visibility" class="w-full mt-1 border border-slatecalm-200 rounded-xl px-3 py-2 text-sm">
                    <option value="internal">Intern (nur Agenten)</option>
                    <option value="public">Öffentlich (auch Kundenportal)</option>
                </select>
            </div>
            <div class="md:col-span-3">
                <label class="text-xs font-medium text-slate-600">Kategorie</label>
                <select wire:model="category_id" class="w-full mt-1 border border-slatecalm-200 rounded-xl px-3 py-2 text-sm">
                    <option value="">— Kategorie wählen —</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}">{{ str_repeat('— ', $category->depth) }}{{ $category->name }}</option>
                    @endforeach
                </select>
                @error('category_id') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>
            <div class="md:col-span-3">
                <label class="text-xs font-medium text-slate-600">Inhalt (Markdown)</label>
                <textarea wire:model="body" rows="14" class="w-full mt-1 border border-slatecalm-200 rounded-xl px-3 py-2 text-sm font-mono"></textarea>
                @error('body') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>
        </div>
        <div class="flex justify-end">
            <button wire:click="publish" class="px-5 py-2 bg-calm-600 hover:bg-calm-700 text-white rounded-xl text-sm font-medium">Veröffentlichen (neue Version)</button>
        </div>
    </div>

    @if ($article)
        <div class="bg-white border border-slatecalm-200 rounded-2xl p-6">
            <h2 class="font-semibold text-slatecalm-900 mb-4">Versionen</h2>
            <ul class="divide-y divide-slatecalm-100">
                @foreach ($versions as $version)
                    <li wire:key="version-{{ $version->id }}" class="py-2 flex items-center justify-between text-sm">
                        <span>
                            <span class="font-medium text-slatecalm-900">v{{ $version->version_number }}</span>
                            <span class="text-slate-500">· {{ $version->created_at->format('d.m.Y H:i') }} · {{ $version->author?->name ?? '—' }}</span>
                            @if ($version->id === $article->current_version_id)
                                <span class="ml-2 text-xs px-2 py-0.5 rounded bg-calm-100 text-calm-800">aktuell</span>
                            @endif
                        </span>
                        @if ($version->id !== $article->current_version_id)
                            <span class="flex gap-2">
                                <button wire:click="compare({{ $version->id }})" class="text-xs px-2.5 py-1 rounded-lg bg-slatecalm-100 text-slate-700">Diff zu aktuell</button>
                                <button wire:click="rollback({{ $version->id }})" wire:confirm="Diese Version als neue aktuelle Version wiederherstellen?" class="text-xs px-2.5 py-1 rounded-lg bg-ocean-50 text-ocean-700">Wiederherstellen</button>
                            </span>
                        @endif
                    </li>
                @endforeach
            </ul>

            @if ($compared)
                <h3 class="mt-6 mb-2 text-sm font-semibold text-slatecalm-900">Änderungen v{{ $compared->version_number }} → aktuell</h3>
                <pre class="text-xs bg-slatecalm-50 border border-slatecalm-200 rounded-xl p-3 overflow-x-auto">@foreach ($diff as $row)<span class="block {{ $row['type'] === 'added' ? 'bg-calm-100 text-calm-800' : ($row['type'] === 'removed' ? 'bg-red-50 text-red-700 line-through' : 'text-slate-600') }}">{{ $row['type'] === 'added' ? '+ ' : ($row['type'] === 'removed' ? '- ' : '  ') }}{{ $row['line'] }}</span>@endforeach</pre>
            @endif
        </div>
    @endif
</div>
