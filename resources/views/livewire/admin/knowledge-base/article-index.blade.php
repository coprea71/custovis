<div class="space-y-6">
    <div class="flex items-center justify-between gap-4">
        <h1 class="text-xl font-semibold text-slatecalm-900">Wissensdatenbank — Artikel</h1>
        <div class="flex gap-2">
            <a href="{{ route('admin.kb.categories') }}" class="px-4 py-2 bg-slatecalm-100 text-slate-700 rounded-xl text-sm font-medium">Kategorien</a>
            <a href="{{ route('admin.kb.articles.create') }}" class="px-4 py-2 bg-calm-600 hover:bg-calm-700 text-white rounded-xl text-sm font-medium">Neuer Artikel</a>
        </div>
    </div>

    <input type="search" wire:model.live.debounce.300ms="search" placeholder="Volltextsuche..." class="w-full border border-slatecalm-200 rounded-xl px-3 py-2 text-sm">

    <div class="bg-white border border-slatecalm-200 rounded-2xl divide-y divide-slatecalm-200">
        @forelse ($articles as $article)
            <a wire:key="article-{{ $article->id }}" href="{{ route('admin.kb.articles.edit', $article) }}" class="p-4 flex items-center justify-between hover:bg-slatecalm-50">
                <div>
                    <p class="font-medium text-slatecalm-900">{{ $article->title }}</p>
                    <p class="text-xs text-slate-400">{{ $article->category->name }} · aktualisiert {{ $article->updated_at->format('d.m.Y H:i') }}</p>
                </div>
                <span class="text-xs px-2.5 py-1 rounded-lg {{ $article->visibility === 'public' ? 'bg-ocean-50 text-ocean-700' : 'bg-slatecalm-100 text-slate-500' }}">
                    {{ $article->visibility === 'public' ? 'Öffentlich' : 'Intern' }}
                </span>
            </a>
        @empty
            <p class="p-4 text-sm text-slate-400">Keine Artikel gefunden.</p>
        @endforelse
    </div>

    {{ $articles->links() }}
</div>
