@php
    $money = fn (int $cents) => \App\Models\Invoice::money($cents);
    $qty = fn ($value) => rtrim(rtrim(number_format((float) $value, 2, ',', '.'), '0'), ',');
    $input = 'w-full mt-1 border border-slatecalm-200 rounded-xl px-3 py-2 text-sm';
    $buyerName = $invoice->buyer['name'] ?? ($invoice->customer?->company ?: $invoice->customer?->name);
@endphp
<div class="flex-1 overflow-y-auto p-6 space-y-6">
    @include('livewire.agent.team.invoicing.partials.header', ['title' => 'Rechnungen — '.$team->name])

    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h2 class="text-lg font-semibold text-slatecalm-900">{{ $invoice->title() }} {{ $invoice->number ?? '(Entwurf)' }}</h2>
            <p class="text-sm text-slate-500">
                {{ $buyerName ?? 'Kunde gelöscht' }}
                @if ($invoice->issue_date) · ausgestellt am {{ $invoice->issue_date->format('d.m.Y') }} · fällig am {{ $invoice->due_date->format('d.m.Y') }} @endif
            </p>
        </div>
        @include('livewire.agent.team.invoicing.partials.status', ['invoice' => $invoice])
    </div>

    @if ($status)
        <div class="text-sm text-calm-800 bg-calm-50 rounded-xl px-4 py-2" role="status">{{ $status }}</div>
    @endif
    @error('issue')
        <div class="text-sm text-red-700 bg-red-50 rounded-xl px-4 py-2" role="alert">
            <p class="font-medium">Die Rechnung kann noch nicht ausgestellt werden:</p>
            <ul class="list-disc ml-5">@foreach ($errors->get('issue') as $message) <li>{{ $message }}</li> @endforeach</ul>
        </div>
    @enderror
    @error('send') <div class="text-sm text-red-700 bg-red-50 rounded-xl px-4 py-2" role="alert">{{ $message }}</div> @enderror

    @if ($invoice->cancelledInvoice)
        <p class="text-sm text-slate-600">Storniert die Rechnung <a href="{{ route('agent.team.invoices.show', [$team, $invoice->cancelledInvoice]) }}" class="text-calm-700 underline">{{ $invoice->cancelledInvoice->number }}</a>.</p>
    @endif
    @if ($cancellation)
        <p class="text-sm text-slate-600">Storniert durch <a href="{{ route('agent.team.invoices.show', [$team, $cancellation]) }}" class="text-calm-700 underline">{{ $cancellation->number }}</a>.</p>
    @endif

    <div class="bg-white border border-slatecalm-200 rounded-2xl overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="text-left text-xs text-slate-500">
                <tr><th class="p-3">Pos.</th><th class="p-3">Beschreibung</th><th class="p-3 text-right">Menge</th><th class="p-3 text-right">Einzelpreis</th><th class="p-3 text-right">Gesamt</th><th class="p-3"></th></tr>
            </thead>
            <tbody class="divide-y divide-slatecalm-100">
                @forelse ($invoice->items as $item)
                    <tr wire:key="item-{{ $item->id }}">
                        <td class="p-3">{{ $item->position }}</td>
                        <td class="p-3">{{ $item->description }}</td>
                        <td class="p-3 text-right whitespace-nowrap">{{ $qty($item->quantity) }} {{ $item->unitLabel() }}</td>
                        <td class="p-3 text-right whitespace-nowrap">{{ $money($item->unit_price_cents) }}</td>
                        <td class="p-3 text-right whitespace-nowrap">{{ $money($item->net_cents) }}</td>
                        <td class="p-3 text-right">
                            @if ($invoice->isDraft() && ! $readOnly)
                                <button type="button" wire:click="removeItem({{ $item->id }})" class="text-red-700" aria-label="Position entfernen">&times;</button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="p-3 text-slate-400">Noch keine Positionen.</td></tr>
                @endforelse
            </tbody>
            <tfoot class="text-sm">
                <tr><td colspan="4" class="p-3 text-right">Summe netto</td><td class="p-3 text-right">{{ $money($invoice->net_cents) }}</td><td></td></tr>
                <tr><td colspan="4" class="p-3 text-right">{{ $invoice->small_business ? 'Keine USt. (§ 19 UStG)' : 'Umsatzsteuer '.$qty($invoice->tax_rate).' %' }}</td><td class="p-3 text-right">{{ $money($invoice->tax_cents) }}</td><td></td></tr>
                <tr class="font-semibold"><td colspan="4" class="p-3 text-right">Gesamtbetrag</td><td class="p-3 text-right">{{ $money($invoice->gross_cents) }}</td><td></td></tr>
            </tfoot>
        </table>
    </div>

    @if ($invoice->isDraft())
        @unless ($readOnly)
            @include('livewire.agent.team.invoicing.partials.draft-forms', ['input' => $input])
        @endunless
    @else
        <div class="bg-white border border-slatecalm-200 rounded-2xl p-6 flex flex-wrap gap-2 text-sm">
            <a href="{{ route('agent.team.invoices.file', [$team, $invoice, 'pdf']) }}" class="px-4 py-2 rounded-xl bg-slatecalm-100 text-slate-700 font-medium">PDF (ZUGFeRD)</a>
            <a href="{{ route('agent.team.invoices.file', [$team, $invoice, 'xml']) }}" class="px-4 py-2 rounded-xl bg-slatecalm-100 text-slate-700 font-medium">XML (XRechnung)</a>
            @unless ($readOnly)
                <button type="button" wire:click="send" wire:confirm="Rechnung an {{ $invoice->buyer['email'] }} senden?" class="px-4 py-2 rounded-xl bg-calm-600 hover:bg-calm-700 text-white font-medium">
                    {{ $invoice->sent_at ? 'Erneut senden' : 'Per E-Mail senden' }}
                </button>
                @if ($invoice->status === 'issued' && ! $invoice->isCancellation())
                    @unless ($invoice->paid_at)
                        <button type="button" wire:click="markPaid" class="px-4 py-2 rounded-xl bg-slatecalm-100 text-slate-700 font-medium">Als bezahlt markieren</button>
                    @endunless
                    <button type="button" wire:click="cancel" wire:confirm="Rechnung stornieren? Es wird eine Stornorechnung mit eigener Nummer erstellt." class="px-4 py-2 rounded-xl bg-red-50 text-red-700 font-medium">Stornieren</button>
                @endif
            @endunless
        </div>
        <p class="text-xs text-slate-500">
            @if ($invoice->sent_at) Gesendet am {{ $invoice->sent_at->format('d.m.Y H:i') }} Uhr. @endif
            @if ($invoice->paid_at) Bezahlt am {{ $invoice->paid_at->format('d.m.Y') }}. @endif
            Ausgestellte Rechnungen sind unveränderbar; Korrekturen erfolgen per Storno.
        </p>
    @endif
</div>
