@php
    $money = fn (int $cents) => \App\Models\Invoice::money($cents);
    $input = 'border border-slatecalm-200 rounded-xl px-3 py-2 text-sm';
@endphp
<div class="flex-1 overflow-y-auto p-6 space-y-6">
    @include('livewire.agent.team.invoicing.partials.header', ['title' => 'Rechnungen — '.$team->name])

    @if ($status)
        <div class="text-sm text-calm-800 bg-calm-50 rounded-xl px-4 py-2" role="status">{{ $status }}</div>
    @endif

    @unless ($readOnly)
        <div class="grid grid-cols-1 xl:grid-cols-2 gap-6 items-start">
            <div class="bg-white border border-slatecalm-200 rounded-2xl p-6 space-y-3">
                <h2 class="font-semibold text-slatecalm-900">Sammelrechnungen für einen Zeitraum</h2>
                <p class="text-xs text-slate-500">Legt je Kunde eine Rechnung als Entwurf an, die alle offenen abrechenbaren Zeiten aus den Tickets des Teams im Zeitraum zusammenfasst, eine Position je Ticket.</p>
                <div class="grid grid-cols-2 gap-3">
                    <label class="text-xs text-slate-600">Von<input type="date" wire:model="periodFrom" class="w-full mt-1 {{ $input }}"></label>
                    <label class="text-xs text-slate-600">Bis<input type="date" wire:model="periodTo" max="{{ today()->toDateString() }}" class="w-full mt-1 {{ $input }}"></label>
                </div>
                @error('periodFrom') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                @error('periodTo') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                <div class="flex justify-end">
                    <button type="button" wire:click="createCollective" class="px-5 py-2 bg-calm-600 hover:bg-calm-700 text-white rounded-xl text-sm font-medium">Sammelrechnungen anlegen</button>
                </div>
            </div>

            <div class="bg-white border border-slatecalm-200 rounded-2xl p-6 space-y-3">
                <h2 class="font-semibold text-slatecalm-900">Einzelne Rechnung</h2>
                <p class="text-xs text-slate-500">Übernimmt alle offenen abrechenbaren Zeiten des Kunden aus Tickets des Teams. Kunden mit offenen Zeiten stehen oben.</p>
                <input type="search" wire:model.live.debounce.300ms="customerSearch" placeholder="Kunde suchen..." maxlength="255" aria-label="Kunde suchen" class="w-full {{ $input }}">
                <select wire:model="customerId" aria-label="Kunde" class="w-full {{ $input }}">
                    <option value="">Kunde wählen...</option>
                    @foreach ($customers as $customer)
                        <option value="{{ $customer->id }}">{{ $customer->company ? $customer->company.' – ' : '' }}{{ $customer->name }} ({{ $customer->email }}){{ $customer->has_open_time ? ' · offene Zeiten' : '' }}</option>
                    @endforeach
                </select>
                @error('customerId') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                <div class="flex justify-end">
                    <button type="button" wire:click="createDraft" class="px-5 py-2 bg-slatecalm-100 text-slate-700 rounded-xl text-sm font-medium">Entwurf anlegen</button>
                </div>
            </div>
        </div>
    @endunless

    <div class="flex flex-wrap gap-3 items-center">
        <select wire:model.live="filter" aria-label="Filter" class="{{ $input }}">
            @foreach (\App\Livewire\Agent\Team\Invoicing\InvoiceManager::STATUSES as $key => $label)
                <option value="{{ $key }}">{{ $label }}</option>
            @endforeach
        </select>
        <input type="search" wire:model.live.debounce.300ms="search" placeholder="Rechnungsnummer oder Kunde..." maxlength="255" aria-label="Rechnungen suchen" class="flex-1 min-w-48 {{ $input }}">
    </div>

    <div class="bg-white border border-slatecalm-200 rounded-2xl divide-y divide-slatecalm-200">
        @forelse ($invoices as $invoice)
            <a href="{{ route('agent.team.invoices.show', [$team, $invoice]) }}" wire:key="invoice-{{ $invoice->id }}" class="p-4 flex flex-wrap items-center justify-between gap-3 hover:bg-slatecalm-50">
                <div>
                    <p class="font-medium text-slatecalm-900">
                        {{ $invoice->number ?? 'Entwurf #'.$invoice->id }}
                        @if ($invoice->isCancellation()) <span class="text-xs text-slate-500">(Storno)</span> @endif
                    </p>
                    <p class="text-xs text-slate-400">{{ $invoice->buyer['name'] ?? ($invoice->customer?->company ?: $invoice->customer?->name) ?? '—' }} · {{ $invoice->issue_date?->format('d.m.Y') ?? 'nicht ausgestellt' }}</p>
                </div>
                <div class="flex items-center gap-3 text-sm">
                    <span class="font-medium text-slatecalm-900">{{ $money($invoice->gross_cents) }}</span>
                    @include('livewire.agent.team.invoicing.partials.status', ['invoice' => $invoice])
                </div>
            </a>
        @empty
            <p class="p-4 text-sm text-slate-400">Keine Rechnungen gefunden.</p>
        @endforelse
    </div>

    {{ $invoices->links() }}
</div>
