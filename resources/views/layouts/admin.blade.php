<x-layouts.app title="Administration" body-class="bg-slatecalm-50">
    <header class="bg-white border-b border-slatecalm-200 px-6 py-3 flex flex-wrap items-center gap-x-6 gap-y-2">
        <span class="font-semibold text-slatecalm-900">{{ config('app.name') }} — Administration</span>
        <nav class="flex flex-wrap gap-1 text-sm">
            @foreach ([
                'admin.dashboard' => 'Dashboard',
                'admin.mailboxes.index' => 'Mailboxen',
                'admin.service-catalog.index' => 'Service-Katalog',
                'admin.kb.articles.index' => 'Wissensdatenbank',
                'admin.settings.theme' => 'Theme',
            ] as $route => $label)
                <a href="{{ route($route) }}"
                   class="px-3 py-1.5 rounded-lg {{ request()->routeIs($route) ? 'bg-calm-100 text-calm-800 font-medium' : 'text-slate-600 hover:bg-slatecalm-100' }}">{{ $label }}</a>
            @endforeach
        </nav>
    </header>

    <main class="p-6 max-w-5xl mx-auto">
        {{ $slot }}
    </main>
</x-layouts.app>
