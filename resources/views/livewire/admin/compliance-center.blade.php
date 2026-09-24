<div class="space-y-6">
    <h1 class="text-xl font-semibold text-slatecalm-900">Datenschutz &amp; Compliance</h1>

    @if ($result)
        <p class="text-sm rounded-xl px-4 py-3 bg-calm-50 text-calm-800">{{ $result }}</p>
    @endif

    <section class="bg-white border border-slatecalm-200 rounded-2xl p-6 space-y-4">
        <div>
            <h2 class="font-semibold text-slatecalm-900">Betroffenenrechte (DSGVO Art. 15 / 17)</h2>
            <p class="text-sm text-slate-500">Kennung der betroffenen Person: Kunden-ID, E-Mail-Adresse oder Telefonnummer (exakt wie im Ticket gespeichert).</p>
        </div>
        <label class="block text-sm">Kennung
            <input type="text" wire:model="identifier" class="w-full mt-1 border border-slatecalm-200 rounded-xl px-3 py-2">
        </label>
        @error('identifier') <p class="text-xs text-red-600">{{ $message }}</p> @enderror

        <div class="flex flex-wrap gap-3">
            <button type="button" wire:click="export" class="px-4 py-2 bg-calm-600 hover:bg-calm-700 text-white rounded-xl text-sm font-medium">Auskunft exportieren (JSON)</button>
        </div>

        <div class="border-t border-slatecalm-200 pt-4 space-y-2">
            <p class="text-sm text-slate-600">Anonymisierung ersetzt Name, E-Mail, Telefonnummer und eingehende Nachrichten der Person, löscht deren Anhänge und kann nicht rückgängig gemacht werden. Tickets bleiben für Statistik und Audit erhalten.</p>
            <label class="block text-sm">Kennung zur Bestätigung wiederholen
                <input type="text" wire:model="confirmation" class="w-full mt-1 border border-slatecalm-200 rounded-xl px-3 py-2">
            </label>
            @error('confirmation') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
            <button type="button" wire:click="anonymize" wire:confirm="Personenbezogene Daten endgültig anonymisieren?" class="px-4 py-2 bg-red-50 text-red-700 rounded-xl text-sm font-medium">Anonymisieren</button>
        </div>
    </section>

    <section class="bg-white border border-slatecalm-200 rounded-2xl p-6 space-y-4">
        <div>
            <h2 class="font-semibold text-slatecalm-900">Aufbewahrungsfristen</h2>
            <p class="text-sm text-slate-500">In Tagen, 0 = unbegrenzt. Wird täglich automatisch angewendet.</p>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
            <label>Team-Chat-Nachrichten löschen nach<input type="number" min="0" wire:model="retention.chat_days" class="w-full mt-1 border border-slatecalm-200 rounded-xl px-3 py-2"></label>
            <label>Audit-Log-Einträge löschen nach<input type="number" min="0" wire:model="retention.audit_log_days" class="w-full mt-1 border border-slatecalm-200 rounded-xl px-3 py-2"></label>
            <label>Geschlossene Tickets anonymisieren nach<input type="number" min="0" wire:model="retention.closed_ticket_days" class="w-full mt-1 border border-slatecalm-200 rounded-xl px-3 py-2"></label>
        </div>
        <p class="text-xs text-slate-400">Techniker-Standortdaten werden nach {{ config('custovis.field_service.location_retention_days') }} Tagen gelöscht (Konfiguration <code>CUSTOVIS_LOCATION_RETENTION_DAYS</code>).</p>
        @error('retention.*') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
        <div class="flex justify-end">
            <button type="button" wire:click="saveRetention" class="px-4 py-2 bg-calm-600 hover:bg-calm-700 text-white rounded-xl text-sm font-medium">Speichern</button>
        </div>
    </section>
</div>
