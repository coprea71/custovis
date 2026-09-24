<x-layouts.app title="Kundenportal" body-class="bg-slatecalm-50">
    <header class="bg-white border-b border-slatecalm-200">
        <div class="max-w-4xl mx-auto px-4 py-3 flex flex-wrap items-center justify-between gap-3">
            <a href="{{ route('portal.tickets.index') }}" class="flex items-center gap-2">
                <span class="w-8 h-8 rounded-xl bg-calm-600 flex items-center justify-center text-white font-bold text-sm">C</span>
                <span class="font-semibold text-slatecalm-900 text-sm">{{ config('app.name') }}</span>
            </a>
            <nav class="flex flex-wrap items-center gap-1 text-sm">
                @foreach (array_filter(['portal.tickets.index' => 'Meine Anfragen', 'portal.requests.create' => app(\App\Services\ModuleAccess::class)->enabled('service-catalog') ? 'Neue Anfrage' : null, 'portal.kb.index' => app(\App\Services\ModuleAccess::class)->enabled('knowledge-base') ? 'Hilfe-Artikel' : null]) as $route => $label)
                    <a href="{{ route($route) }}" class="px-3 py-1.5 rounded-lg {{ request()->routeIs($route) ? 'bg-calm-100 text-calm-800 font-medium' : 'text-slate-600 hover:bg-slatecalm-100' }}">{{ $label }}</a>
                @endforeach
                <form method="POST" action="{{ route('portal.logout') }}">
                    @csrf
                    <button type="submit" class="px-3 py-1.5 rounded-lg text-slate-500 hover:bg-slatecalm-100">Abmelden</button>
                </form>
            </nav>
        </div>
    </header>

    <main class="max-w-4xl mx-auto px-4 py-6">
        {{ $slot }}
    </main>
</x-layouts.app>
