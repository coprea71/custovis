@php
    $address = trim($customer->street.', '.trim($customer->postal_code.' '.$customer->city), ', ');
    $details = array_filter(['Telefon' => $customer->phone, 'Mobil' => $customer->mobile, 'Adresse' => $address]);
    $input = 'w-full mt-1 border border-slatecalm-200 rounded-xl px-3 py-2 text-sm';
@endphp
<div class="mb-4 rounded-xl bg-slatecalm-50 p-3 text-sm space-y-2">
    @forelse ($details as $label => $value)
        <div>
            <p class="text-xs text-slate-500">{{ $label }}</p>
            @if ($label === 'Adresse')
                <p class="text-slatecalm-900">{{ $value }}</p>
            @else
                <a href="tel:{{ preg_replace('/[^0-9+]/', '', $value) }}" class="text-calm-700 hover:underline">{{ $value }}</a>
            @endif
        </div>
    @empty
        <p class="text-xs text-slate-500">Keine Kontaktdaten hinterlegt.</p>
    @endforelse
    @if ($customer->notes)
        <div>
            <p class="text-xs text-slate-500">Notizen (intern)</p>
            <p class="text-slatecalm-900 whitespace-pre-line">{{ $customer->notes }}</p>
        </div>
    @endif
    @can('customers.manage')
        <button type="button" wire:click="edit" class="text-xs font-medium text-calm-700 hover:underline">
            {{ $customer->hasContactDetails() ? 'Kontaktdaten bearbeiten' : 'Kontaktdaten hinterlegen' }}
        </button>
    @endcan

    @if ($editing)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 p-4" wire:keydown.escape.window="cancel">
            <div class="w-full max-w-md max-h-full overflow-y-auto bg-white rounded-2xl shadow-xl p-6" role="dialog" aria-modal="true" aria-labelledby="customer-contact-title">
                <h2 id="customer-contact-title" class="font-semibold text-slatecalm-900 mb-1">Kontaktdaten</h2>
                <p class="text-xs text-slate-500 mb-4">{{ $customer->name }}</p>

                <form wire:submit="save" class="space-y-3">
                    @foreach (['phone' => ['Telefon', 'tel', 30], 'mobile' => ['Mobil', 'tel', 30], 'street' => ['Straße und Hausnummer', 'text', 255]] as $field => [$label, $type, $max])
                        <div>
                            <label for="customer-contact-{{ $field }}" class="text-xs font-medium text-slate-600">{{ $label }}</label>
                            <input id="customer-contact-{{ $field }}" type="{{ $type }}" wire:model="{{ $field }}" maxlength="{{ $max }}" class="{{ $input }}">
                            @error($field) <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                        </div>
                    @endforeach
                    <div class="grid grid-cols-3 gap-2">
                        <div>
                            <label for="customer-contact-postal_code" class="text-xs font-medium text-slate-600">PLZ</label>
                            <input id="customer-contact-postal_code" type="text" wire:model="postal_code" maxlength="10" class="{{ $input }}">
                        </div>
                        <div class="col-span-2">
                            <label for="customer-contact-city" class="text-xs font-medium text-slate-600">Ort</label>
                            <input id="customer-contact-city" type="text" wire:model="city" maxlength="100" class="{{ $input }}">
                        </div>
                    </div>
                    @error('postal_code') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                    @error('city') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                    <div>
                        <label for="customer-contact-notes" class="text-xs font-medium text-slate-600">Notizen (intern)</label>
                        <textarea id="customer-contact-notes" wire:model="notes" rows="3" maxlength="5000" class="{{ $input }}"></textarea>
                        @error('notes') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" wire:click="cancel" class="text-sm px-4 py-2 rounded-xl bg-slatecalm-100 text-slate-700">Abbrechen</button>
                        <button type="submit" class="text-sm px-5 py-2 rounded-xl bg-calm-600 hover:bg-calm-700 text-white font-medium">Speichern</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
