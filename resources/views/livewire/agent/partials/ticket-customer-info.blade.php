{{-- Contact details stay collapsed so the sidebar keeps its compact default view. --}}
@php
    $address = trim($customer->street.', '.trim($customer->postal_code.' '.$customer->city), ', ');
    $details = array_filter(['Telefon' => $customer->phone, 'Mobil' => $customer->mobile, 'Adresse' => $address]);
@endphp
<div x-show="customerInfo" x-cloak class="mb-4 rounded-xl bg-slatecalm-50 p-3 text-sm space-y-2">
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
</div>
