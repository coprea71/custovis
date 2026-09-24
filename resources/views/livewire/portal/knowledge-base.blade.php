<div class="space-y-4">
    <h1 class="text-xl font-semibold text-slatecalm-900">Hilfe-Artikel</h1>

    @if ($article)
        <a href="{{ route('portal.kb.index') }}" class="text-sm text-slate-500 hover:text-calm-700">&larr; Alle Artikel</a>
        <article class="bg-white border border-slatecalm-200 rounded-2xl p-6">
            <h2 class="text-2xl font-semibold text-slatecalm-900 mb-4">{{ $article->title }}</h2>
            <div class="prose-kb text-sm text-slate-700 space-y-3">{!! $article->bodyHtml() !!}</div>
            <div class="mt-8">
                <livewire:knowledge-base.feedback-widget :article-id="$article->id" :key="'feedback-'.$article->id" />
            </div>
        </article>
    @else
        <input type="search" wire:model.live.debounce.300ms="search" placeholder="Wonach suchen Sie?" aria-label="Hilfe-Artikel durchsuchen" class="w-full border border-slatecalm-200 rounded-xl px-3 py-2 text-sm">

        <div class="bg-white border border-slatecalm-200 rounded-2xl divide-y divide-slatecalm-200">
            @forelse ($articles as $item)
                <a wire:key="kb-{{ $item->id }}" href="{{ route('portal.kb.show', $item->id) }}" class="block p-4 hover:bg-slatecalm-50">
                    <p class="font-medium text-slatecalm-900">{{ $item->title }}</p>
                </a>
            @empty
                <p class="p-4 text-sm text-slate-400">Keine passenden Artikel gefunden.</p>
            @endforelse
        </div>
    @endif
</div>
