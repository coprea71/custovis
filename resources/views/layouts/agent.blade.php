<x-layouts.app title="Agent" body-class="selection:bg-calm-200 selection:text-calm-900 flex flex-col md:flex-row overflow-hidden">
    {{-- Mobile header: the sidebar is hidden below md, so the same navigation opens as a panel. --}}
    <div x-data="{ open: false }" class="md:hidden bg-slatecalm-50 border-b border-slatecalm-200 shrink-0">
        <div class="h-14 px-4 flex items-center justify-between">
            <span class="font-semibold text-slatecalm-900 text-sm">{{ config('app.name') }}</span>
            <button type="button" @click="open = ! open" :aria-expanded="open" aria-label="Menü öffnen" class="px-3 py-1.5 rounded-lg bg-white border border-slatecalm-200 text-sm">Menü</button>
        </div>
        <div x-show="open" x-cloak class="border-t border-slatecalm-200 max-h-[70vh] overflow-y-auto">
            @include('layouts.partials.agent-nav')
            <x-account-menu class="px-6 pb-4" />
        </div>
    </div>

    <aside class="w-64 bg-slatecalm-50 border-r border-slatecalm-200 flex-col justify-between shrink-0 z-20 hidden md:flex">
        <div class="overflow-y-auto">
            <div class="h-16 px-6 flex items-center border-b border-slatecalm-200">
                <a href="{{ route('agent.tickets.index') }}" class="flex items-center space-x-2.5">
                    <span class="w-9 h-9 rounded-xl bg-calm-600 flex items-center justify-center text-white shadow-sm font-bold">C</span>
                    <span>
                        <span class="block font-semibold text-slatecalm-900 text-sm leading-tight">{{ config('app.name') }}</span>
                        <span class="text-[11px] text-calm-600 font-medium">Agent-Bereich</span>
                    </span>
                </a>
            </div>

            @include('layouts.partials.agent-nav')
        </div>

        <div class="p-4 border-t border-slatecalm-200 bg-white/60 space-y-3">
            <div class="flex items-center space-x-3">
                <div class="w-9 h-9 rounded-full bg-calm-200 text-calm-800 font-semibold flex items-center justify-center text-sm border border-calm-300">
                    {{ Str::of(auth()->user()->name)->substr(0, 1)->upper() }}
                </div>
                <div class="min-w-0">
                    <p class="text-sm font-medium text-slatecalm-900 truncate">{{ auth()->user()->name }}</p>
                    <p class="text-xs text-calm-600 truncate">{{ auth()->user()->email }}</p>
                </div>
            </div>
            <x-account-menu />
        </div>
    </aside>

    <main class="flex-1 flex flex-col h-full overflow-hidden">
        {{ $slot }}
    </main>
</x-layouts.app>
