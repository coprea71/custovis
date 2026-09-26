<x-layouts.app title="Administration">
    <header class="bg-white border-b border-slatecalm-200 px-4 md:px-6 py-3 space-y-2">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <span class="font-semibold text-slatecalm-900">{{ config('app.name') }} — Administration</span>
            <div class="flex items-center gap-3">
                <span class="text-xs text-slate-500">{{ auth()->user()->name }}</span>
                <x-account-menu area="admin" />
            </div>
        </div>
        {{-- Scrolls horizontally on phones instead of wrapping into five rows. --}}
        <nav class="flex md:flex-wrap gap-1 text-sm overflow-x-auto -mx-4 px-4 md:mx-0 md:px-0" aria-label="Administration">
            @foreach (\App\Support\AdminNavigation::for(auth()->user()) as $route => $label)
                <a href="{{ route($route) }}" @if (request()->routeIs($route.'*')) aria-current="page" @endif
                   class="shrink-0 whitespace-nowrap px-3 py-1.5 rounded-lg {{ request()->routeIs($route.'*') ? 'bg-calm-100 text-calm-800 font-medium' : 'text-slate-600 hover:bg-slatecalm-100' }}">{{ $label }}</a>
            @endforeach
        </nav>
    </header>

    <main class="p-4 md:p-6 max-w-5xl mx-auto">
        {{ $slot }}
    </main>
</x-layouts.app>
