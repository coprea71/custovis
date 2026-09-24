<div class="p-6 max-w-3xl">
    <h1 class="text-xl font-semibold text-slatecalm-900 mb-1">ERP-/Shop-Anbindung — {{ $team->name }}</h1>
    <p class="text-sm text-slate-500 mb-6">
        @if ($readOnly)
            Nur-Lese-Ansicht (System-Admin-Audit).
        @else
            Kundendaten aus Odoo oder Shopware werden im Ticket nur auf Anfrage abgerufen, kurz zwischengespeichert und nie dauerhaft übernommen.
            Bitte im Zielsystem einen reinen Lese-Integrationsnutzer verwenden.
        @endif
    </p>

    @if (! $readOnly)
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6 bg-white border border-slatecalm-200 rounded-2xl p-6">
            <div>
                <label class="text-xs font-medium text-slate-600">Bezeichnung</label>
                <input type="text" wire:model="name" class="w-full mt-1 border border-slatecalm-200 rounded-xl px-3 py-2 text-sm">
                @error('name') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="text-xs font-medium text-slate-600">System</label>
                <select wire:model.live="type" @disabled($editingId) class="w-full mt-1 border border-slatecalm-200 rounded-xl px-3 py-2 text-sm">
                    <option value="odoo">Odoo (JSON-RPC)</option>
                    <option value="shopware">Shopware 6 (Admin-API)</option>
                </select>
                @error('type') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>
            <div class="md:col-span-2">
                <label class="text-xs font-medium text-slate-600">Basis-URL (https://…)</label>
                <input type="url" wire:model="baseUrl" class="w-full mt-1 border border-slatecalm-200 rounded-xl px-3 py-2 text-sm">
                @error('baseUrl') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>

            @if ($type === 'odoo')
                <div>
                    <label class="text-xs font-medium text-slate-600">Datenbank</label>
                    <input type="text" wire:model="database" class="w-full mt-1 border border-slatecalm-200 rounded-xl px-3 py-2 text-sm">
                    @error('database') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="text-xs font-medium text-slate-600">Login des Integrationsnutzers</label>
                    <input type="text" wire:model="login" class="w-full mt-1 border border-slatecalm-200 rounded-xl px-3 py-2 text-sm">
                    @error('login') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>
            @else
                <div>
                    <label class="text-xs font-medium text-slate-600">Access-Key-ID (Client-ID)</label>
                    <input type="text" wire:model="clientId" class="w-full mt-1 border border-slatecalm-200 rounded-xl px-3 py-2 text-sm">
                    @error('clientId') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>
            @endif
            <div>
                <label class="text-xs font-medium text-slate-600">{{ $type === 'odoo' ? 'API-Key' : 'Secret-Access-Key' }}</label>
                <input type="password" wire:model="secret" autocomplete="new-password" class="w-full mt-1 border border-slatecalm-200 rounded-xl px-3 py-2 text-sm">
                @if ($editingId)
                    <p class="text-[11px] text-slate-400 mt-1">Leer lassen, um die gespeicherten Zugangsdaten zu behalten. Bei geänderter URL Pflicht.</p>
                @endif
                @error('secret') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="md:col-span-2">
                <label class="text-xs font-medium text-slate-600">Angezeigte Felder (je Zeile: feldname=Bezeichnung)</label>
                <textarea wire:model="fieldMappingText" rows="4" class="w-full mt-1 border border-slatecalm-200 rounded-xl px-3 py-2 text-sm font-mono"></textarea>
                <p class="text-[11px] text-slate-400 mt-1">Nur diese Felder werden im ERP abgefragt und im Ticket angezeigt (Datenminimierung).</p>
                @error('fieldMappingText') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="md:col-span-2 flex gap-2">
                <button wire:click="save" class="flex-1 py-2 bg-calm-600 hover:bg-calm-700 text-white rounded-xl text-sm font-medium">
                    {{ $editingId ? 'Änderungen speichern' : 'Verbindung anlegen' }}
                </button>
                @if ($editingId)
                    <button wire:click="cancelEdit" class="px-4 py-2 bg-slatecalm-100 text-slate-700 rounded-xl text-sm">Abbrechen</button>
                @endif
            </div>
        </div>
    @endif

    <div class="bg-white border border-slatecalm-200 rounded-2xl divide-y divide-slatecalm-200">
        @forelse ($connections as $connection)
            <div class="p-4 flex items-center justify-between gap-3">
                <div class="min-w-0">
                    <p class="font-medium text-slatecalm-900">{{ $connection->name }} <span class="text-xs text-slate-500">({{ ucfirst($connection->type) }})</span></p>
                    <p class="text-xs text-slate-500 truncate">
                        {{ $connection->base_url }} ·
                        {{ $connection->is_active ? 'Aktiv' : 'Deaktiviert' }} ·
                        {{ count($connection->field_mapping ?? []) }} Felder
                    </p>
                </div>
                @if (! $readOnly)
                    <div class="flex gap-2 shrink-0">
                        <button wire:click="edit({{ $connection->id }})" class="text-xs px-3 py-1.5 rounded-lg font-medium bg-slatecalm-100 text-slate-700 hover:bg-slatecalm-200">Bearbeiten</button>
                        <button wire:click="toggleActive({{ $connection->id }})"
                                class="text-xs px-3 py-1.5 rounded-lg font-medium {{ $connection->is_active ? 'bg-red-50 text-red-700 hover:bg-red-100' : 'bg-calm-50 text-calm-700 hover:bg-calm-100' }}">
                            {{ $connection->is_active ? 'Deaktivieren' : 'Aktivieren' }}
                        </button>
                    </div>
                @endif
            </div>
        @empty
            <p class="p-4 text-sm text-slate-400">Noch keine Verbindung angelegt.</p>
        @endforelse
    </div>
</div>
