@props(['title', 'items' => [], 'labels' => []])
@php($max = max([1, ...array_values($items)]))
<div class="bg-white border border-slatecalm-200 rounded-2xl p-5">
    <h2 class="text-sm font-semibold text-slatecalm-900 mb-3">{{ $title }}</h2>
    <ul class="space-y-2">
        @forelse ($items as $label => $total)
            <li>
                <div class="flex justify-between text-sm">
                    <span class="text-slate-600">{{ $labels[$label] ?? $label }}</span>
                    <span class="font-medium text-slatecalm-900">{{ $total }}</span>
                </div>
                <div class="mt-1 h-1.5 rounded-full bg-slatecalm-100">
                    <div class="h-1.5 rounded-full bg-calm-500" style="width: {{ round($total / $max * 100) }}%"></div>
                </div>
            </li>
        @empty
            <li class="text-sm text-slate-400">Keine Daten.</li>
        @endforelse
    </ul>
</div>
