<div class="flex-1 flex overflow-hidden">
    {{-- Below md only one pane fits: the list hides while a topic is open. --}}
    <section class="w-full md:w-96 shrink-0 border-r border-slatecalm-200 bg-white {{ $current ? 'hidden md:flex' : 'flex' }} flex-col">
        <div class="p-4 border-b border-slatecalm-200">
            <h1 class="text-base font-semibold text-slatecalm-900 mb-3">Hilfe</h1>
            <input type="search" wire:model.live.debounce.300ms="search" placeholder="Hilfethema suchen..." aria-label="Hilfethemen durchsuchen" class="w-full border border-slatecalm-200 rounded-xl px-3 py-2 text-sm">
        </div>
        <nav class="flex-1 overflow-y-auto" aria-label="Hilfethemen">
            @foreach (\App\Support\HelpTopics::GROUPS as $groupKey => $groupLabel)
                @continue(! $groups->has($groupKey))
                <p class="px-4 pt-4 pb-1 text-[11px] font-semibold uppercase tracking-wide text-slate-400">{{ $groupLabel }}</p>
                <ul class="divide-y divide-slatecalm-100">
                    @foreach ($groups->get($groupKey) as $slug => $item)
                        <li wire:key="help-{{ $slug }}">
                            <a href="{{ route('agent.help.show', $slug) }}" @if ($topic === $slug) aria-current="page" @endif
                               class="block px-4 py-3 hover:bg-slatecalm-50 {{ $topic === $slug ? 'bg-calm-50' : '' }}">
                                <p class="text-sm font-medium text-slatecalm-900">{{ $item['title'] }}</p>
                                <p class="text-xs text-slate-400">{{ $item['summary'] }}</p>
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endforeach
            @if ($groups->isEmpty())
                <p class="p-4 text-sm text-slate-400">Kein passendes Hilfethema gefunden.</p>
            @endif
        </nav>
    </section>

    <article class="flex-1 overflow-y-auto p-4 md:p-8 {{ $current ? 'block' : 'hidden md:block' }}">
        @if ($current)
            <a href="{{ route('agent.help.index') }}" class="md:hidden inline-block mb-4 text-sm text-slate-500 hover:text-calm-700">&larr; Alle Hilfethemen</a>
            <p class="text-xs text-slate-400 mb-1">{{ \App\Support\HelpTopics::GROUPS[$current['group']] }}</p>
            <h2 class="text-2xl font-semibold text-slatecalm-900 mb-6">{{ $current['title'] }}</h2>
            <div class="prose-kb max-w-3xl text-sm text-slate-700 space-y-3">{!! $body !!}</div>
        @else
            <h2 class="text-2xl font-semibold text-slatecalm-900 mb-3">Wie können wir helfen?</h2>
            <p class="text-sm text-slate-600 max-w-2xl">Wählen Sie links ein Thema. Jede Anleitung führt Schritt für Schritt durch eine Aufgabe. Es werden nur Themen angezeigt, die Sie mit Ihren Berechtigungen auch nutzen können.</p>
        @endif
    </article>
</div>
