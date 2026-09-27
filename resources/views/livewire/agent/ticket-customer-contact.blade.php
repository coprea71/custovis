@php
    $address = trim($customer->street.', '.trim($customer->postal_code.' '.$customer->city), ', ');
    $details = array_filter(['Telefon' => $customer->phone, 'Mobil' => $customer->mobile, 'Adresse' => $address]);
    $input = 'w-full mt-1 border border-slatecalm-200 rounded-lg px-2 py-1.5 text-sm bg-white';
@endphp
<div class="mb-4 rounded-xl bg-slatecalm-50 p-3 text-sm space-y-2">
    @if ($editing)
        <form wire:submit="save" class="space-y-2">
            @foreach (['phone' => ['Telefon', 'tel', 30], 'mobile' => ['Mobil', 'tel', 30], 'street' => ['Straße und Hausnummer', 'text', 255]] as $field => [$label, $type, $max])
                <label class="block text-xs text-slate-600">{{ $label }}
                    <input type="{{ $type }}" wire:model="{{ $field }}" maxlength="{{ $max }}" class="{{ $input }}">
                </label>
                @error($field) <p class="text-xs text-red-600">{{ $message }}</p> @enderror
            @endforeach
            <div class="grid grid-cols-3 gap-2">
                <label class="block text-xs text-slate-600">PLZ<input type="text" wire:model="postal_code" maxlength="10" class="{{ $input }}"></label>
                <label class="col-span-2 block text-xs text-slate-600">Ort<input type="text" wire:model="city" maxlength="100" class="{{ $input }}"></label>
            </div>
            @error('postal_code') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
            @error('city') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
            <label class="block text-xs text-slate-600">Notizen (intern)
                <textarea wire:model="notes" rows="3" maxlength="5000" class="{{ $input }}"></textarea>
            </label>
            @error('notes') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
            <div class="flex justify-end gap-2">
                <button type="button" wire:click="cancel" class="px-3 py-1.5 rounded-lg bg-white text-slate-700 text-xs font-medium border border-slatecalm-200">Abbrechen</button>
                <button type="submit" class="px-3 py-1.5 rounded-lg bg-calm-600 hover:bg-calm-700 text-white text-xs font-medium">Speichern</button>
            </div>
        </form>
    @else
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
    @endif
</div>
