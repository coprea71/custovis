<section class="bg-white border border-slatecalm-200 rounded-2xl p-6 space-y-4">
    <div>
        <h2 class="font-semibold text-slatecalm-900 mb-1">SLA je Kunde</h2>
        <p class="text-xs text-slate-500">Vertraglich vereinbarte Fristen für einzelne Kunden. Sie haben Vorrang vor der SLA des Teams mit derselben Priorität und laufen in den Geschäftszeiten des jeweiligen Teams. Leer = es gilt die Team-SLA.</p>
    </div>

    @if ($withSla->isNotEmpty())
        <div class="flex flex-wrap gap-2 text-xs">
            @foreach ($withSla as $customer)
                <button type="button" wire:click="selectCustomer({{ $customer->id }})"
                        class="px-2.5 py-1 rounded-lg {{ $customerId === $customer->id ? 'bg-calm-600 text-white' : 'bg-calm-100 text-calm-800' }}">
                    {{ $customer->company ?: $customer->name }} ({{ $customer->sla_policies_count }})
                </button>
            @endforeach
        </div>
    @endif

    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
        <input type="search" wire:model.live.debounce.300ms="search" placeholder="Kunde suchen..." maxlength="255" aria-label="Kunde suchen"
               class="border border-slatecalm-200 rounded-xl px-3 py-2 text-sm">
        <select wire:change="selectCustomer($event.target.value)" aria-label="Kunde" class="md:col-span-2 border border-slatecalm-200 rounded-xl px-3 py-2 text-sm">
            <option value="">Kunde wählen...</option>
            @foreach ($customers as $customer)
                <option value="{{ $customer->id }}" @selected($customerId === $customer->id)>{{ $customer->company ? $customer->company.' – ' : '' }}{{ $customer->name }} ({{ $customer->email }})</option>
            @endforeach
        </select>
    </div>

    @if ($status) <p class="text-sm rounded-xl px-4 py-3 bg-calm-50 text-calm-800" role="status">{{ $status }}</p> @endif

    @if ($customerId)
        <form wire:submit="save" class="space-y-3">
            <table class="w-full text-sm">
                <thead><tr class="text-left text-slate-500"><th class="py-1">Priorität</th><th>Erste Reaktion (Min.)</th><th>Lösung (Min.)</th></tr></thead>
                <tbody>
                    @foreach (\App\Models\Ticket::PRIORITY_LABELS as $priority => $label)
                        <tr>
                            <td class="py-1.5">{{ $label }}</td>
                            <td><input type="number" min="1" wire:model="policies.{{ $priority }}.response" aria-label="Kunde Reaktion {{ $label }}" class="w-28 border border-slatecalm-200 rounded-lg px-2 py-1"></td>
                            <td><input type="number" min="1" wire:model="policies.{{ $priority }}.resolution" aria-label="Kunde Lösung {{ $label }}" class="w-28 border border-slatecalm-200 rounded-lg px-2 py-1"></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <p class="text-xs text-red-600">{{ $errors->first() }}</p>
            <div class="flex justify-end">
                <button type="submit" class="px-5 py-2 bg-calm-600 hover:bg-calm-700 text-white rounded-xl text-sm font-medium">Kunden-SLA speichern</button>
            </div>
        </form>
    @endif
</section>
