<div>
    <h3 class="text-xs font-semibold text-slate-500 uppercase tracking-wide mb-2">ERP-Kundendaten</h3>

    @if ($results === null)
        <button type="button" wire:click="loadCustomer" wire:loading.attr="disabled"
                class="w-full text-xs px-3 py-2 rounded-lg bg-ocean-50 text-ocean-700 font-medium">
            <span wire:loading.remove wire:target="loadCustomer">Kundendaten laden</span>
            <span wire:loading wire:target="loadCustomer">Wird geladen…</span>
        </button>
    @elseif ($results === [])
        <p class="text-xs text-slate-400">Keine aktive ERP-Anbindung oder keine E-Mail-Adresse als Kundenreferenz vorhanden.</p>
    @else
        <div class="space-y-3">
            @foreach ($results as $result)
                <div class="rounded-xl border border-slatecalm-200 p-3">
                    <p class="text-xs font-medium text-slatecalm-900 mb-1">{{ $result['name'] }}</p>
                    @if ($result['status'] === 'error')
                        <p class="text-xs text-amber-700">ERP derzeit nicht erreichbar. Bitte später erneut versuchen.</p>
                    @elseif ($result['status'] === 'not_found')
                        <p class="text-xs text-slate-400">Kein passender Kunde gefunden.</p>
                    @else
                        <dl class="text-xs space-y-1">
                            @foreach ($result['fields'] as $label => $value)
                                <div class="flex justify-between gap-2">
                                    <dt class="text-slate-500">{{ $label }}</dt>
                                    <dd class="text-slatecalm-900 text-right break-words">{{ $value ?? '—' }}</dd>
                                </div>
                            @endforeach
                        </dl>
                    @endif
                </div>
            @endforeach
        </div>
        <button type="button" wire:click="loadCustomer" class="mt-2 text-[11px] text-ocean-700 hover:underline">Erneut laden</button>
    @endif
</div>
