<x-layouts.app title="Service-Portal" body-class="bg-canvas" :scripts="false">
    <x-slot:head>
        <meta name="description" content="{{ config('app.name') }}: Support-Anfragen stellen und verfolgen, Hilfe-Artikel lesen und – für Mitarbeitende – Tickets, Einsätze und Wissensdatenbank bearbeiten.">
        <link rel="canonical" href="{{ rtrim(config('app.url'), '/') }}/">
        <script type="application/ld+json">
            {!! json_encode([
                '@@context' => 'https://schema.org',
                '@type' => 'WebApplication',
                'name' => config('app.name'),
                'url' => rtrim(config('app.url'), '/').'/',
                'applicationCategory' => 'BusinessApplication',
                'description' => 'Service-Portal mit Ticketsystem, Kundenportal und Wissensdatenbank.',
                'inLanguage' => 'de',
            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}
        </script>
    </x-slot:head>

    <main class="min-h-full flex flex-col">
        <header class="max-w-5xl w-full mx-auto px-4 py-6 flex items-center gap-2.5">
            <span class="w-9 h-9 rounded-xl bg-calm-600 flex items-center justify-center text-white font-bold" aria-hidden="true">C</span>
            <span class="font-semibold text-slatecalm-900">{{ config('app.name') }}</span>
        </header>

        <section class="max-w-5xl w-full mx-auto px-4 py-10 flex-1">
            <h1 class="text-3xl sm:text-4xl font-semibold text-slatecalm-900 max-w-2xl">Wie können wir Ihnen helfen?</h1>
            <p class="mt-4 text-slate-600 max-w-2xl">
                Über das Kundenportal stellen Sie Anfragen, verfolgen deren Bearbeitungsstand und
                finden Antworten in unseren Hilfe-Artikeln. Mitarbeitende melden sich im Agenten-Bereich an.
            </p>

            <div class="mt-10 grid grid-cols-1 md:grid-cols-3 gap-4">
                <a href="{{ route('portal.login') }}" class="bg-white border border-slatecalm-200 rounded-2xl p-6 hover:border-calm-400">
                    <h2 class="font-semibold text-slatecalm-900">Kundenportal</h2>
                    <p class="mt-1 text-sm text-slate-500">Anfragen stellen, Status verfolgen, mit dem Support schreiben.</p>
                </a>
                @if (app(\App\Services\ModuleAccess::class)->enabled('knowledge-base'))
                    <a href="{{ route('portal.kb.index') }}" class="bg-white border border-slatecalm-200 rounded-2xl p-6 hover:border-calm-400">
                        <h2 class="font-semibold text-slatecalm-900">Hilfe-Artikel</h2>
                        <p class="mt-1 text-sm text-slate-500">Antworten auf häufige Fragen (nach Anmeldung im Kundenportal).</p>
                    </a>
                @endif
                <a href="{{ route('login') }}" class="bg-white border border-slatecalm-200 rounded-2xl p-6 hover:border-calm-400">
                    <h2 class="font-semibold text-slatecalm-900">Für Mitarbeitende</h2>
                    <p class="mt-1 text-sm text-slate-500">Anmeldung für Agenten, Administratoren und Techniker.</p>
                </a>
            </div>
        </section>

        <footer class="max-w-5xl w-full mx-auto px-4 py-6 text-xs text-slate-400">
            {{ config('app.name') }} · betrieben mit Custovis – Service Suite (Open Source, AGPLv3)
        </footer>
    </main>
</x-layouts.app>
