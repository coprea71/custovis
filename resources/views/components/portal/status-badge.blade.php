@props(['status'])
@php
    $labels = ['open' => 'Offen', 'pending' => 'In Bearbeitung', 'closed' => 'Abgeschlossen'];
@endphp
<span class="shrink-0 text-xs px-2.5 py-1 rounded-lg {{ $status === 'closed' ? 'bg-slatecalm-100 text-slate-500' : 'bg-calm-100 text-calm-800' }}">
    {{ $labels[$status] ?? $status }}
</span>
