<div class="space-y-4">
    <h1 class="text-xl font-semibold text-slatecalm-900">Anleitungen</h1>

    @if ($topic)
        <a href="{{ route('portal.help.index') }}" class="text-sm text-slate-500 hover:text-calm-700">&larr; Alle Anleitungen</a>
        <article class="bg-white border border-slatecalm-200 rounded-2xl p-6">
            <h2 class="text-2xl font-semibold text-slatecalm-900 mb-4">{{ $topics[$topic]['title'] }}</h2>
            <div class="prose-kb text-sm text-slate-700 space-y-3">{!! $body !!}</div>
        </article>
    @else
        <p class="text-sm text-slate-600">So bedienen Sie das Kundenportal – Schritt für Schritt.</p>

        <div class="bg-white border border-slatecalm-200 rounded-2xl divide-y divide-slatecalm-200">
            @foreach ($topics as $slug => $item)
                <a wire:key="help-{{ $slug }}" href="{{ route('portal.help.show', $slug) }}" class="block p-4 hover:bg-slatecalm-50">
                    <p class="font-medium text-slatecalm-900">{{ $item['title'] }}</p>
                    <p class="text-sm text-slate-500">{{ $item['summary'] }}</p>
                </a>
            @endforeach
        </div>
    @endif
</div>
