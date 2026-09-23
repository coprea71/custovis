<div class="p-6 max-w-3xl">
    <h1 class="text-xl font-semibold text-slatecalm-900 mb-1">API-Keys — {{ $team->name }}</h1>
    <p class="text-sm text-slate-500 mb-6">
        @if ($readOnly)
            Nur-Lese-Ansicht (System-Admin-Audit).
        @else
            Externe Ticket-Anlage per API. Der Schlüssel wird nur einmalig im Klartext angezeigt.
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
        <div class="flex gap-2 mb-6">
            <input type="text" wire:model="newClientName" placeholder="Name des Clients (z. B. \"Webshop-Integration\")"
                   class="flex-1 border border-slatecalm-200 rounded-xl px-3 py-2 text-sm">
            <button wire:click="createClient" class="px-4 py-2 bg-calm-600 hover:bg-calm-700 text-white rounded-xl text-sm font-medium">Anlegen</button>
        </div>
        @error('newClientName') <p class="text-xs text-red-600 -mt-4 mb-4">{{ $message }}</p> @enderror
    @endif

    <div class="bg-white border border-slatecalm-200 rounded-2xl divide-y divide-slatecalm-200">
        @forelse ($clients as $client)
            <div class="p-4 flex items-center justify-between">
                <div>
                    <p class="font-medium text-slatecalm-900">{{ $client->name }}</p>
                    <p class="text-xs text-slate-500">
                        Erstellt von {{ $client->creator->name }} ·
                        {{ $client->isRevoked() ? 'Widerrufen '.$client->revoked_at->diffForHumans() : 'Aktiv' }} ·
                        Letzte Nutzung: {{ optional($client->tokens->first())->last_used_at?->diffForHumans() ?? 'nie' }}
                    </p>
                </div>
                @if (! $readOnly && ! $client->isRevoked())
                    <button wire:click="revokeClient({{ $client->id }})" wire:confirm="API-Key wirklich widerrufen?"
                            class="text-xs px-3 py-1.5 rounded-lg font-medium bg-red-50 text-red-700 hover:bg-red-100">
                        Widerrufen
                    </button>
                @endif
            </div>
        @empty
            <p class="p-4 text-sm text-slate-400">Noch kein API-Key angelegt.</p>
        @endforelse
    </div>
</div>
