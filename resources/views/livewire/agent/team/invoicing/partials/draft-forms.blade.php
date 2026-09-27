<div class="grid grid-cols-1 xl:grid-cols-2 gap-6 items-start">
    <form wire:submit="addItem" class="bg-white border border-slatecalm-200 rounded-2xl p-6 space-y-3 text-sm">
        <div class="flex items-center justify-between gap-2">
            <h3 class="font-semibold text-slatecalm-900">Position hinzufügen</h3>
            <button type="button" wire:click="importTime" class="px-3 py-1.5 rounded-lg bg-slatecalm-100 text-slate-700 text-xs font-medium">Offene Zeiten übernehmen</button>
        </div>
        <label class="block text-xs text-slate-600">Beschreibung
            <input type="text" wire:model="itemDescription" maxlength="500" class="{{ $input }}">
        </label>
        @error('itemDescription') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
        <div class="grid grid-cols-3 gap-3">
            <label class="block text-xs text-slate-600">Menge
                <input type="text" wire:model="itemQuantity" inputmode="decimal" maxlength="10" class="{{ $input }}">
            </label>
            <label class="block text-xs text-slate-600">Einheit
                <select wire:model="itemUnit" class="{{ $input }}">
                    @foreach (\App\Models\InvoiceItem::UNITS as $code => $label)
                        <option value="{{ $code }}">{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            <label class="block text-xs text-slate-600">Einzelpreis netto €
                <input type="text" wire:model="itemPrice" inputmode="decimal" maxlength="10" placeholder="90,00" class="{{ $input }}">
            </label>
        </div>
        @error('itemQuantity') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
        @error('itemPrice') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
        <div class="flex justify-end">
            <button type="submit" class="px-4 py-2 bg-slatecalm-100 text-slate-700 rounded-xl font-medium">Hinzufügen</button>
        </div>
    </form>

    <form wire:submit="saveDetails" class="bg-white border border-slatecalm-200 rounded-2xl p-6 space-y-3 text-sm">
        <h3 class="font-semibold text-slatecalm-900">Angaben</h3>
        <div class="grid grid-cols-2 gap-3">
            <label class="block text-xs text-slate-600">Leistung von
                <input type="date" wire:model="service_from" class="{{ $input }}">
            </label>
            <label class="block text-xs text-slate-600">Leistung bis
                <input type="date" wire:model="service_to" class="{{ $input }}">
            </label>
        </div>
        <p class="text-xs text-slate-500">Ohne Leistungszeitraum gilt das Rechnungsdatum als Leistungsdatum.</p>
        @error('service_from') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
        @error('service_to') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
        <label class="block text-xs text-slate-600">Hinweistext auf der Rechnung
            <textarea wire:model="notes" rows="3" maxlength="2000" class="{{ $input }}"></textarea>
        </label>
        @error('notes') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
        <div class="flex flex-wrap justify-end gap-2">
            <button type="button" wire:click="deleteDraft" wire:confirm="Entwurf löschen? Übernommene Zeiten werden wieder freigegeben." class="px-4 py-2 bg-red-50 text-red-700 rounded-xl font-medium">Entwurf löschen</button>
            <button type="submit" class="px-4 py-2 bg-slatecalm-100 text-slate-700 rounded-xl font-medium">Speichern</button>
            <button type="button" wire:click="issue" wire:confirm="Rechnung jetzt verbindlich ausstellen? Danach ist sie nicht mehr änderbar." class="px-4 py-2 bg-calm-600 hover:bg-calm-700 text-white rounded-xl font-medium">Ausstellen</button>
        </div>
    </form>
</div>
