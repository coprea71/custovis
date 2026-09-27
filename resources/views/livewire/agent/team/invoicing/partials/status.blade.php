@php
    [$label, $class] = match (true) {
        $invoice->isDraft() => ['Entwurf', 'bg-slatecalm-100 text-slate-700'],
        $invoice->status === 'cancelled' => ['Storniert', 'bg-red-50 text-red-700'],
        $invoice->isCancellation() => ['Storno', 'bg-slatecalm-100 text-slate-700'],
        $invoice->paid_at !== null => ['Bezahlt', 'bg-calm-100 text-calm-800'],
        $invoice->due_date?->isPast() => ['Überfällig', 'bg-amber-50 text-amber-800'],
        default => ['Offen', 'bg-amber-50 text-amber-800'],
    };
@endphp
<span class="text-xs px-2 py-1 rounded-md font-medium {{ $class }}">{{ $label }}</span>
