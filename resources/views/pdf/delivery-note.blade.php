<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Lieferschein {{ $delivery->delivery_note_number }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1e293b; }
        h1 { font-size: 18px; margin-bottom: 4px; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th, td { border-bottom: 1px solid #e1e8e6; padding: 6px 4px; text-align: left; }
        .muted { color: #64748b; }
        .signature { margin-top: 24px; }
        .signature img { height: 80px; border-bottom: 1px solid #1e293b; }
    </style>
</head>
<body>
    <h1>Lieferschein {{ $delivery->delivery_note_number }}</h1>
    <p class="muted">{{ config('app.name') }} · Ticket #{{ $appointment->ticket_id }} · Einsatz #{{ $appointment->id }}</p>

    <p>
        <strong>Lieferadresse:</strong> {{ $appointment->address }}<br>
        <strong>Ausgeliefert am:</strong> {{ $delivery->delivered_at->format('d.m.Y H:i') }} Uhr
    </p>

    <table>
        <thead><tr><th>Position</th><th>Menge</th></tr></thead>
        <tbody>
            @forelse ($parts as $part)
                <tr><td>{{ $part->description }}</td><td>{{ rtrim(rtrim(number_format($part->quantity, 2, ',', '.'), '0'), ',') }} {{ $part->unit }}</td></tr>
            @empty
                @if (! $delivery->items_text)
                    <tr><td colspan="2" class="muted">Keine Positionen erfasst.</td></tr>
                @endif
            @endforelse
        </tbody>
    </table>

    @if ($delivery->items_text)
        <p style="white-space: pre-line; margin-top: 12px;">{{ $delivery->items_text }}</p>
    @endif

    <div class="signature">
        <p><strong>Empfang bestätigt durch:</strong> {{ $delivery->recipient_name }}
            @if ($delivery->recipient_email) · {{ $delivery->recipient_email }} @endif
            @if ($delivery->recipient_phone) · {{ $delivery->recipient_phone }} @endif
        </p>
        <img src="{{ $signatureDataUri }}" alt="Unterschrift">
    </div>
</body>
</html>
