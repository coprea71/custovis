@props(['href', 'active' => false, 'badge' => null])
<a href="{{ $href }}" @if ($active) aria-current="page" @endif
   {{ $attributes->merge(['class' => 'flex items-center justify-between px-3 py-2.5 rounded-lg text-sm font-medium '.($active ? 'bg-calm-100 text-calm-800' : 'text-slate-600 hover:bg-slatecalm-100')]) }}>
    <span>{{ $slot }}</span>
    @if ($badge)
        <span class="text-[11px] px-1.5 rounded-full bg-calm-600 text-white">{{ $badge }}</span>
    @endif
</a>
