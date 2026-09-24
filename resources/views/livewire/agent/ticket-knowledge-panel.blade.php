<div>
    <h3 class="text-xs font-semibold text-slate-500 uppercase tracking-wide mb-2">Wissensdatenbank</h3>

    @if ($ticket->resolvedWithArticle)
        <div class="mb-3 text-xs bg-calm-50 border border-calm-200 rounded-lg p-2">
            Gelöst mit: <a href="{{ route('agent.kb.show', $ticket->resolvedWithArticle) }}" class="font-medium text-calm-800 hover:underline">{{ $ticket->resolvedWithArticle->title }}</a>
            <button type="button" wire:click="clearResolved" class="ml-1 text-slate-400 hover:text-red-600">&times;</button>
        </div>
    @endif

    <input type="search" wire:model.live.debounce.300ms="kbSearch" placeholder="Artikel suchen..." class="w-full border border-slatecalm-200 rounded-lg px-2 py-1 text-xs">

    <ul class="mt-2 space-y-2">
        @foreach ($results as $article)
            <li wire:key="kb-result-{{ $article->id }}" class="text-xs border border-slatecalm-200 rounded-lg p-2">
                <p class="font-medium text-slatecalm-900">{{ $article->title }}</p>
                <p class="text-slate-400 mb-1.5">{{ $article->visibility === 'public' ? 'Öffentlich' : 'Intern' }}</p>
                <div class="flex gap-1.5">
                    <button type="button" wire:click="insert({{ $article->id }})" class="px-2 py-1 rounded bg-slatecalm-100 text-slate-700">In Antwort einfügen</button>
                    <button type="button" wire:click="markResolved({{ $article->id }})" class="px-2 py-1 rounded bg-calm-100 text-calm-800">Als Lösung markieren</button>
                </div>
            </li>
        @endforeach
    </ul>
</div>
