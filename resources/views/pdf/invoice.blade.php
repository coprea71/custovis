@php
    $seller = $invoice->seller;
    $buyer = $invoice->buyer;
    $qty = fn ($value) => rtrim(rtrim(number_format((float) $value, 2, ',', '.'), '0'), ',');
@endphp
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>{{ $invoice->title() }} {{ $invoice->number }}</title>
    <style>
        @page { margin: 20mm 18mm 28mm 20mm; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #1e293b; }
        .sender { font-size: 7.5px; color: #64748b; border-bottom: 0.5px solid #94a3b8; display: inline-block; margin-bottom: 4px; }
        .address { height: 110px; }
        .meta { position: absolute; top: 0; right: 0; width: 210px; }
        .meta td { padding: 1px 0; }
        h1 { font-size: 16px; margin: 24px 0 8px; }
        table.items { width: 100%; border-collapse: collapse; margin-top: 12px; }
        table.items th { text-align: left; border-bottom: 1px solid #1e293b; padding: 5px 4px; }
        table.items td { border-bottom: 0.5px solid #cbd5e1; padding: 5px 4px; vertical-align: top; }
        .num { text-align: right; white-space: nowrap; }
        table.totals { width: 260px; margin-left: auto; margin-top: 10px; border-collapse: collapse; }
        table.totals td { padding: 3px 4px; }
        table.totals tr.gross td { font-weight: bold; border-top: 1px solid #1e293b; }
        .muted { color: #64748b; }
        footer { position: fixed; bottom: -18mm; left: 0; right: 0; font-size: 7.5px; color: #64748b; border-top: 0.5px solid #cbd5e1; padding-top: 4px; }
        footer td { vertical-align: top; width: 33%; }
    </style>
</head>
<body>
    <div style="position: relative;">
        <div class="address">
            <span class="sender">{{ $seller['company'] }} · {{ $seller['street'] }} · {{ $seller['postal_code'] }} {{ $seller['city'] }}</span><br>
            {{ $buyer['name'] }}<br>
            @if ($buyer['contact']) {{ $buyer['contact'] }}<br> @endif
            {{ $buyer['street'] }}<br>
            {{ $buyer['postal_code'] }} {{ $buyer['city'] }}
            @if ($buyer['country'] !== 'DE')<br>{{ $buyer['country'] }}@endif
        </div>
        <table class="meta">
            <tr><td>{{ $invoice->title() }}-Nr.:</td><td class="num">{{ $invoice->number }}</td></tr>
            <tr><td>Rechnungsdatum:</td><td class="num">{{ $invoice->issue_date->format('d.m.Y') }}</td></tr>
            <tr><td>Leistungszeitraum:</td><td class="num">
                @if ($invoice->service_from && $invoice->service_to)
                    {{ $invoice->service_from->format('d.m.Y') }} – {{ $invoice->service_to->format('d.m.Y') }}
                @else
                    {{ $invoice->issue_date->format('d.m.Y') }}
                @endif
            </td></tr>
            <tr><td>Kundennummer:</td><td class="num">{{ $buyer['customer_number'] }}</td></tr>
            <tr><td>Ihre Referenz:</td><td class="num">{{ $buyer['reference'] }}</td></tr>
            @if ($buyer['vat_id'])
                <tr><td>Ihre USt-IdNr.:</td><td class="num">{{ $buyer['vat_id'] }}</td></tr>
            @endif
        </table>
    </div>

    <h1>{{ $invoice->title() }} {{ $invoice->number }}</h1>
    @if ($invoice->notes)
        <p style="white-space: pre-line;">{{ $invoice->notes }}</p>
    @endif

    <table class="items">
        <thead>
            <tr><th>Pos.</th><th>Beschreibung</th><th class="num">Menge</th><th class="num">Einzelpreis</th><th class="num">Gesamt</th></tr>
        </thead>
        <tbody>
            @foreach ($invoice->items as $item)
                <tr>
                    <td>{{ $item->position }}</td>
                    <td>{{ $item->description }}</td>
                    <td class="num">{{ $qty($item->quantity) }} {{ $item->unitLabel() }}</td>
                    <td class="num">{{ \App\Models\Invoice::money($item->unit_price_cents) }}</td>
                    <td class="num">{{ \App\Models\Invoice::money($item->net_cents) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totals">
        <tr><td>Summe netto</td><td class="num">{{ \App\Models\Invoice::money($invoice->net_cents) }}</td></tr>
        @unless ($invoice->small_business)
            <tr><td>Umsatzsteuer {{ $qty($invoice->tax_rate) }} %</td><td class="num">{{ \App\Models\Invoice::money($invoice->tax_cents) }}</td></tr>
        @endunless
        <tr class="gross"><td>Gesamtbetrag</td><td class="num">{{ \App\Models\Invoice::money($invoice->gross_cents) }}</td></tr>
    </table>

    @if ($invoice->small_business)
        <p>Gemäß § 19 UStG wird keine Umsatzsteuer berechnet.</p>
    @endif

    @if ($invoice->isCancellation())
        <p>Diese Stornorechnung hebt die Rechnung {{ $invoice->cancelledInvoice->number }} vom {{ $invoice->cancelledInvoice->issue_date->format('d.m.Y') }} vollständig auf.</p>
    @else
        <p>Bitte überweisen Sie den Gesamtbetrag bis zum <strong>{{ $invoice->due_date->format('d.m.Y') }}</strong> ohne Abzug unter Angabe der Rechnungsnummer auf das unten genannte Konto.</p>
    @endif

    <footer>
        <table style="width: 100%;">
            <tr>
                <td>{{ $seller['company'] }}<br>{{ $seller['street'] }}<br>{{ $seller['postal_code'] }} {{ $seller['city'] }}</td>
                <td>{{ $seller['contact_name'] }}<br>Tel. {{ $seller['phone'] }}<br>{{ $seller['email'] }}</td>
                <td>
                    @if ($seller['bank_name']) {{ $seller['bank_name'] }}<br> @endif
                    IBAN {{ $seller['iban'] }}@if ($seller['bic'])<br>BIC {{ $seller['bic'] }}@endif
                    @if ($seller['vat_id'])<br>USt-IdNr. {{ $seller['vat_id'] }}@endif
                    @if ($seller['tax_number'])<br>St.-Nr. {{ $seller['tax_number'] }}@endif
                </td>
            </tr>
        </table>
    </footer>
</body>
</html>
