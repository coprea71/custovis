<x-layouts.app title="Kundenportal">
    <header class="bg-white border-b border-slatecalm-200">
        <div class="max-w-4xl mx-auto px-4 py-3 flex flex-wrap items-center justify-between gap-3">
            <a href="{{ route('portal.tickets.index') }}" class="flex items-center gap-2">
                <span class="w-8 h-8 rounded-xl bg-calm-600 flex items-center justify-center text-white font-bold text-sm">C</span>
                <span class="font-semibold text-slatecalm-900 text-sm">{{ config('app.name') }}</span>
            </a>
            <nav class="flex flex-wrap items-center gap-1 text-sm">
                @php
                    $signedIn = auth('customer')->check();
                    $modules = app(\App\Services\ModuleAccess::class);
                    $navItems = array_filter([
                        'portal.tickets.index' => $signedIn ? 'Meine Anfragen' : null,
                        'portal.requests.create' => $signedIn && $modules->enabled('service-catalog') ? 'Neue Anfrage' : null,
                        'portal.kb.index' => $signedIn && $modules->enabled('knowledge-base') ? 'Hilfe-Artikel' : null,
                        'portal.help.index' => 'Anleitungen',
                    ]);
                @endphp
                @foreach ($navItems as $route => $label)
                    <a href="{{ route($route) }}" class="px-3 py-1.5 rounded-lg {{ request()->routeIs($route) || request()->routeIs(str_replace('.index', '.show', $route)) ? 'bg-calm-100 text-calm-800 font-medium' : 'text-slate-600 hover:bg-slatecalm-100' }}">{{ $label }}</a>
                @endforeach
                @if ($signedIn)
                    <form method="POST" action="{{ route('portal.logout') }}">
                        @csrf
                        <button type="submit" class="px-3 py-1.5 rounded-lg text-slate-500 hover:bg-slatecalm-100">Abmelden</button>
                    </form>
                @else
                    <a href="{{ route('portal.login') }}" class="px-3 py-1.5 rounded-lg text-slate-600 hover:bg-slatecalm-100">Anmelden</a>
                @endif
            </nav>
        </div>
    </header>

    <main class="max-w-4xl mx-auto px-4 py-6">
        {{ $slot }}
    </main>
</x-layouts.app>
