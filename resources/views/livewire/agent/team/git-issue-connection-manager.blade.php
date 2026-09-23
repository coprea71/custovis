<div class="p-6 max-w-3xl">
    <h1 class="text-xl font-semibold text-slatecalm-900 mb-1">GitHub-/GitLab-Issue-Import — {{ $team->name }}</h1>
    <p class="text-sm text-slate-500 mb-6">
        @if ($readOnly)
            Nur-Lese-Ansicht (System-Admin-Audit).
        @else
            Issues aus einem Repository werden automatisch als Tickets angelegt.
        @endif
    </p>

    @if (! $readOnly)
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6 bg-white border border-slatecalm-200 rounded-2xl p-6">
            <div>
                <label class="text-xs font-medium text-slate-600">Anbieter</label>
                <select wire:model="provider" class="w-full mt-1 border border-slatecalm-200 rounded-xl px-3 py-2 text-sm">
                    <option value="github">GitHub</option>
                    <option value="gitlab">GitLab</option>
                </select>
            </div>
            <div>
                <label class="text-xs font-medium text-slate-600">Repository (owner/repo bzw. Projekt-Pfad)</label>
                <input type="text" wire:model="repository" class="w-full mt-1 border border-slatecalm-200 rounded-xl px-3 py-2 text-sm">
                @error('repository') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="text-xs font-medium text-slate-600">Access Token</label>
                <input type="password" wire:model="accessToken" class="w-full mt-1 border border-slatecalm-200 rounded-xl px-3 py-2 text-sm">
                @error('accessToken') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="text-xs font-medium text-slate-600">Webhook Secret</label>
                <input type="password" wire:model="webhookSecret" class="w-full mt-1 border border-slatecalm-200 rounded-xl px-3 py-2 text-sm">
                @error('webhookSecret') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="text-xs font-medium text-slate-600">Sync-Modus</label>
                <select wire:model="syncMode" class="w-full mt-1 border border-slatecalm-200 rounded-xl px-3 py-2 text-sm">
                    <option value="webhook">Webhook (empfohlen)</option>
                    <option value="poll">Polling (alle 5 Minuten)</option>
                </select>
            </div>
            <div class="flex items-end">
                <button wire:click="createConnection" class="w-full py-2 bg-calm-600 hover:bg-calm-700 text-white rounded-xl text-sm font-medium">Verbindung anlegen</button>
            </div>
        </div>
    @endif

    <div class="bg-white border border-slatecalm-200 rounded-2xl divide-y divide-slatecalm-200">
        @forelse ($connections as $connection)
            <div class="p-4 flex items-center justify-between">
                <div>
                    <p class="font-medium text-slatecalm-900">{{ ucfirst($connection->provider) }} — {{ $connection->repository }}</p>
                    <p class="text-xs text-slate-500">
                        {{ $connection->sync_mode }} ·
                        {{ $connection->isRevoked() ? 'Widerrufen' : 'Aktiv' }} ·
                        Letzter Sync: {{ $connection->last_synced_at?->diffForHumans() ?? 'nie' }}
                    </p>
                </div>
                @if (! $readOnly && ! $connection->isRevoked())
                    <button wire:click="revokeConnection({{ $connection->id }})" wire:confirm="Verbindung wirklich widerrufen?"
                            class="text-xs px-3 py-1.5 rounded-lg font-medium bg-red-50 text-red-700 hover:bg-red-100">
                        Widerrufen
                    </button>
                @endif
            </div>
        @empty
            <p class="p-4 text-sm text-slate-400">Noch keine Verbindung angelegt.</p>
        @endforelse
    </div>
</div>
