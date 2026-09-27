@php
    $input = 'w-full mt-1 border border-slatecalm-200 rounded-xl px-3 py-2 text-sm';
    $fields = [
        'Rechnungssteller' => [
            'company' => ['Firmenname', 'text', 255], 'street' => ['Straße und Hausnummer', 'text', 255],
            'postal_code' => ['PLZ', 'text', 10], 'city' => ['Ort', 'text', 100], 'country' => ['Land (ISO-Code, z. B. DE)', 'text', 2],
            'vat_id' => ['USt-IdNr.', 'text', 15], 'tax_number' => ['Steuernummer', 'text', 30],
        ],
        'Kontakt' => [
            'contact_name' => ['Ansprechpartner', 'text', 255], 'email' => ['E-Mail', 'email', 255], 'phone' => ['Telefon', 'tel', 30],
        ],
        'Bankverbindung' => [
            'iban' => ['IBAN', 'text', 34], 'bic' => ['BIC', 'text', 11], 'bank_name' => ['Bank', 'text', 255],
        ],
        'Vorgaben' => [
            'tax_rate' => ['Umsatzsteuersatz in %', 'text', 5], 'hourly_rate' => ['Stundensatz netto in €', 'text', 9],
            'payment_days' => ['Zahlungsziel in Tagen', 'number', 3], 'number_prefix' => ['Präfix der Rechnungsnummer', 'text', 10],
        ],
    ];
@endphp
<div class="space-y-6">
    @include('livewire.admin.invoicing.partials.nav')

    @if ($status)
        <div class="text-sm text-calm-800 bg-calm-50 rounded-xl px-4 py-2" role="status">{{ $status }}</div>
    @endif
    @if ($missing)
        <div class="text-sm text-amber-800 bg-amber-50 rounded-xl px-4 py-2">Für das Ausstellen von E-Rechnungen fehlen noch: {{ implode(', ', $missing) }}.</div>
    @endif

    <form wire:submit="save" class="space-y-6">
        @foreach ($fields as $section => $sectionFields)
            <fieldset class="bg-white border border-slatecalm-200 rounded-2xl p-6">
                <legend class="px-1 font-semibold text-slatecalm-900">{{ $section }}</legend>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @foreach ($sectionFields as $key => [$label, $type, $max])
                        <div>
                            <label for="inv-{{ $key }}" class="text-xs font-medium text-slate-600">{{ $label }}</label>
                            <input id="inv-{{ $key }}" type="{{ $type }}" wire:model="settings.{{ $key }}" maxlength="{{ $max }}" class="{{ $input }}">
                            @error('settings.'.$key) <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                        </div>
                    @endforeach
                    @if ($section === 'Vorgaben')
                        <div>
                            <label for="inv-small-business" class="text-xs font-medium text-slate-600">Kleinunternehmer nach § 19 UStG</label>
                            <select id="inv-small-business" wire:model="settings.small_business" class="{{ $input }}">
                                <option value="0">Nein, Umsatzsteuer ausweisen</option>
                                <option value="1">Ja, keine Umsatzsteuer</option>
                            </select>
                        </div>
                        <div>
                            <label for="inv-mailbox" class="text-xs font-medium text-slate-600">Versand über Mailbox</label>
                            <select id="inv-mailbox" wire:model="settings.mailbox_id" class="{{ $input }}">
                                <option value="">System-Mailer (.env)</option>
                                @foreach ($mailboxes as $mailbox)
                                    <option value="{{ $mailbox->id }}">{{ $mailbox->name }} ({{ $mailbox->email_address }})</option>
                                @endforeach
                            </select>
                            @error('settings.mailbox_id') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                        </div>
                    @endif
                </div>
            </fieldset>
        @endforeach

        <div class="flex justify-end">
            <button type="submit" class="px-5 py-2 bg-calm-600 hover:bg-calm-700 text-white rounded-xl text-sm font-medium">Speichern</button>
        </div>
    </form>
</div>
