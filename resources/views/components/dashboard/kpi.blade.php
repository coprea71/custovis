@props(['label', 'value', 'hint' => null])
<div class="bg-white border border-slatecalm-200 rounded-2xl p-4 sm:p-5 min-w-0">
    <p class="text-xs font-medium text-slate-500 uppercase tracking-wide">{{ $label }}</p>
    <p class="mt-2 text-2xl font-semibold text-slatecalm-900">{{ $value }}</p>
    @if ($hint)
        <p class="mt-1 text-xs text-slate-400 [overflow-wrap:anywhere]">{{ $hint }}</p>
    @endif
</div>
