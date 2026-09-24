@props(['series' => []])
@php($max = max([1, ...array_values($series)]))
<div class="bg-white border border-slatecalm-200 rounded-2xl p-5">
    <h2 class="text-sm font-semibold text-slatecalm-900 mb-3">Ticketaufkommen (14 Tage)</h2>
    <div class="flex items-end gap-1 h-32" role="img" aria-label="Neue Tickets pro Tag der letzten 14 Tage">
        @foreach ($series as $day => $total)
            <div class="flex-1 flex flex-col items-center justify-end h-full" title="{{ \Illuminate\Support\Carbon::parse($day)->format('d.m.') }}: {{ $total }}">
                <div class="w-full rounded-t bg-calm-400" style="height: {{ max(2, round($total / $max * 100)) }}%"></div>
            </div>
        @endforeach
    </div>
    <div class="flex justify-between text-[11px] text-slate-400 mt-1">
        <span>{{ \Illuminate\Support\Carbon::parse(array_key_first($series))->format('d.m.') }}</span>
        <span>{{ \Illuminate\Support\Carbon::parse(array_key_last($series))->format('d.m.') }}</span>
    </div>
</div>
