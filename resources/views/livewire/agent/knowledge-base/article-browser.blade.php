<div class="flex-1 flex overflow-hidden">
    <section class="w-full md:w-96 shrink-0 border-r border-slatecalm-200 bg-white flex flex-col">
        <div class="p-4 border-b border-slatecalm-200">
            <h1 class="text-base font-semibold text-slatecalm-900 mb-3">Wissensdatenbank</h1>
            <input type="search" wire:model.live.debounce.300ms="search" placeholder="Suchen..." class="w-full border border-slatecalm-200 rounded-xl px-3 py-2 text-sm">
        </div>
        <ul class="flex-1 overflow-y-auto divide-y divide-slatecalm-100">
            @forelse ($articles as $item)
                <li wire:key="kb-{{ $item->id }}">
                    <a href="{{ route('agent.kb.show', $item) }}" class="block p-4 hover:bg-slatecalm-50 {{ $article?->id === $item->id ? 'bg-calm-50' : '' }}">
                        <p class="text-sm font-medium text-slatecalm-900">{{ $item->title }}</p>
                        <p class="text-xs text-slate-400">{{ $item->category->name }} · {{ $item->visibility === 'public' ? 'Öffentlich' : 'Intern' }}</p>
                    </a>
                </li>
            @empty
                <li class="p-4 text-sm text-slate-400">Keine Artikel gefunden.</li>
            @endforelse
        </ul>
    </section>

    <article class="flex-1 overflow-y-auto p-8 hidden md:block">
        @if ($article)
            <p class="text-xs text-slate-400 mb-1">{{ $article->category->name }}</p>
            <h2 class="text-2xl font-semibold text-slatecalm-900 mb-6">{{ $article->title }}</h2>
            <div class="prose-kb text-sm text-slate-700 space-y-3">{!! $article->bodyHtml() !!}</div>
            <div class="mt-8">
                <livewire:knowledge-base.feedback-widget :article-id="$article->id" :key="'feedback-'.$article->id" />
            </div>
        @else
            <p class="text-sm text-slate-400">Artikel auswählen.</p>
        @endif
    </article>
</div>
