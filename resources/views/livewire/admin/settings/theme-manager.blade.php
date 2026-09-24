<div class="space-y-6">
    <div>
        <h1 class="text-xl font-semibold text-slatecalm-900">Theme</h1>
        <p class="text-sm text-slate-500">Gilt installationsweit für Agenten-, Admin- und Kundenbereich. Neue Themes erscheinen hier, sobald ein Ordner mit <code>theme.json</code> unter <code>resources/themes/</code> abgelegt wird.</p>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        @forelse ($themes as $theme)
            <div wire:key="theme-{{ $theme->id }}" class="bg-white border rounded-2xl overflow-hidden {{ $theme->id === $activeThemeId ? 'border-calm-500 ring-2 ring-calm-200' : 'border-slatecalm-200' }}">
                <img src="{{ route('themes.preview', $theme) }}" alt="Vorschau {{ $theme->name }}" class="w-full aspect-[16/10] object-cover bg-slatecalm-100" loading="lazy">
                <div class="p-4 flex items-center justify-between gap-2">
                    <div>
                        <p class="font-medium text-slatecalm-900">{{ $theme->name }}</p>
                        @if ($theme->is_default)
                            <p class="text-xs text-slate-400">Standard</p>
                        @endif
                    </div>
                    @if ($theme->id === $activeThemeId)
                        <span class="text-xs px-3 py-1.5 rounded-lg bg-calm-100 text-calm-800 font-medium">Aktiv</span>
                    @else
                        <button wire:click="activate({{ $theme->id }})" class="text-xs px-3 py-1.5 rounded-lg bg-calm-600 hover:bg-calm-700 text-white font-medium">Aktivieren</button>
                    @endif
                </div>
            </div>
        @empty
            <p class="text-sm text-slate-400">Keine gültigen Themes gefunden.</p>
        @endforelse
    </div>
</div>
