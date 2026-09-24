<div class="p-6 max-w-3xl overflow-y-auto">
    <h1 class="text-xl font-semibold text-slatecalm-900 mb-1">API-Keys — {{ $team->name }}</h1>
    <p class="text-sm text-slate-500 mb-6">
        @if ($readOnly)
            Nur-Lese-Ansicht (System-Admin-Audit).
        @else
            Externe Ticket-Anlage per REST-API und/oder Anbindung von KI-Telefonassistenten per MCP (<code>{{ url('/mcp') }}</code>). Der Schlüssel wird nur einmalig im Klartext angezeigt.
        @endif
    </p>

    @if ($plainTextToken)
        <div class="mb-6 p-4 rounded-2xl bg-calm-50 border border-calm-200">
            <p class="text-sm font-medium text-calm-800 mb-1">Neuer API-Key (jetzt kopieren — wird nicht erneut angezeigt):</p>
            <code class="block text-xs bg-white border border-calm-200 rounded-lg p-2 break-all">{{ $plainTextToken }}</code>
            <button wire:click="dismissToken" class="mt-2 text-xs text-calm-700 underline">Schließen</button>
        </div>
    @endif

    @if (! $readOnly)
        <div class="mb-6 bg-white border border-slatecalm-200 rounded-2xl p-4 space-y-3">
            <div class="flex gap-2">
                <input type="text" wire:model="newClientName" placeholder="Name des Clients (z. B. &quot;Webshop&quot; oder &quot;telli&quot;)"
                       class="flex-1 border border-slatecalm-200 rounded-xl px-3 py-2 text-sm">
                <button wire:click="createClient" class="px-4 py-2 bg-calm-600 hover:bg-calm-700 text-white rounded-xl text-sm font-medium">Anlegen</button>
            </div>
            @error('newClientName') <p class="text-xs text-red-600">{{ $message }}</p> @enderror

            <div class="flex flex-wrap gap-4 text-sm text-slate-700">
                <label class="flex items-center gap-2"><input type="checkbox" wire:model.live="allowRest"> REST-API (Ticket-Anlage)</label>
                <label class="flex items-center gap-2"><input type="checkbox" wire:model.live="allowMcp"> MCP (KI-Telefonassistent)</label>
            </div>
            @error('allowRest') <p class="text-xs text-red-600">{{ $message }}</p> @enderror

            @if ($allowMcp)
                <div>
                    <label class="text-xs font-medium text-slate-600">Freigegebene Wissensdatenbank-Kategorien (inkl. Unterkategorien; keine Auswahl = kein KB-Zugriff)</label>
                    <select wire:model="kbCategoryIds" multiple size="5" class="w-full mt-1 border border-slatecalm-200 rounded-xl px-3 py-2 text-sm">
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}">{{ str_repeat('— ', $category->depth) }}{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
        </div>
    @endif

    <div class="bg-white border border-slatecalm-200 rounded-2xl divide-y divide-slatecalm-200">
        @forelse ($clients as $client)
            @php($abilities = $client->tokens->first()?->abilities ?? [])
            <div wire:key="client-{{ $client->id }}" class="p-4">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <p class="font-medium text-slatecalm-900">{{ $client->name }}</p>
                        <p class="text-xs text-slate-500">
                            {{ collect([in_array('tickets.create', $abilities) ? 'REST' : null, in_array('mcp.tools.use', $abilities) ? 'MCP' : null])->filter()->join(' + ') ?: '—' }} ·
                            Erstellt von {{ $client->creator->name }} ·
                            {{ $client->isRevoked() ? 'Widerrufen '.$client->revoked_at->diffForHumans() : 'Aktiv' }} ·
                            Letzte Nutzung: {{ optional($client->tokens->first())->last_used_at?->diffForHumans() ?? 'nie' }}
                        </p>
                        @if (in_array('mcp.tools.use', $abilities))
                            <p class="text-xs text-slate-400">KB-Freigabe: {{ $client->kbCategories->pluck('name')->join(', ') ?: 'keine' }}</p>
                        @endif
                    </div>
                    @if (! $readOnly && ! $client->isRevoked())
                        <div class="flex gap-2 shrink-0">
                            @if (in_array('mcp.tools.use', $abilities))
                                <button wire:click="editKbCategories({{ $client->id }})" class="text-xs px-3 py-1.5 rounded-lg font-medium bg-slatecalm-100 text-slate-700">KB-Freigabe</button>
                            @endif
                            <button wire:click="revokeClient({{ $client->id }})" wire:confirm="API-Key wirklich widerrufen?"
                                    class="text-xs px-3 py-1.5 rounded-lg font-medium bg-red-50 text-red-700 hover:bg-red-100">Widerrufen</button>
                        </div>
                    @endif
                </div>

                @if ($editingClientId === $client->id)
                    <div class="mt-3 flex gap-2 items-start">
                        <select wire:model="editKbCategoryIds" multiple size="5" class="flex-1 border border-slatecalm-200 rounded-xl px-3 py-2 text-sm">
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}">{{ str_repeat('— ', $category->depth) }}{{ $category->name }}</option>
                            @endforeach
                        </select>
                        <button wire:click="saveKbCategories" class="px-4 py-2 bg-calm-600 hover:bg-calm-700 text-white rounded-xl text-sm font-medium">Speichern</button>
                    </div>
                @endif
            </div>
        @empty
            <p class="p-4 text-sm text-slate-400">Noch kein API-Key angelegt.</p>
        @endforelse
    </div>
</div>
