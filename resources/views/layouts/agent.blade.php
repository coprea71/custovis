<!DOCTYPE html>
<html lang="de" class="h-full bg-[#F4F7F6]">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name') }} — Agent</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="h-full font-sans text-slate-700 antialiased selection:bg-calm-200 selection:text-calm-900 flex flex-col md:flex-row overflow-hidden">

    <aside class="w-full md:w-64 bg-slatecalm-50 border-r border-slatecalm-200 flex flex-col justify-between shrink-0 z-20 hidden md:flex">
        <div>
            <div class="h-16 px-6 flex items-center justify-between border-b border-slatecalm-200">
                <div class="flex items-center space-x-2.5">
                    <div class="w-9 h-9 rounded-xl bg-calm-600 flex items-center justify-center text-white shadow-sm font-bold">
                        C
                    </div>
                    <div>
                        <p class="font-semibold text-slatecalm-900 text-sm leading-tight">{{ config('app.name') }}</p>
                        <span class="text-[11px] text-calm-600 font-medium">Agent-Bereich</span>
                    </div>
                </div>
            </div>

            <nav class="p-3 space-y-1">
                <a href="{{ route('agent.tickets.index') }}"
                   class="flex items-center space-x-3 px-3 py-2.5 rounded-lg text-sm font-medium bg-calm-100 text-calm-800">
                    <span>Tickets</span>
                </a>
            </nav>
        </div>

        <div class="p-4 border-t border-slatecalm-200 bg-white/60">
            <div class="flex items-center space-x-3">
                <div class="w-9 h-9 rounded-full bg-calm-200 text-calm-800 font-semibold flex items-center justify-center text-sm border border-calm-300">
                    {{ Str::of(auth()->user()->name)->substr(0, 1)->upper() }}
                </div>
                <div class="min-w-0">
                    <p class="text-sm font-medium text-slatecalm-900 truncate">{{ auth()->user()->name }}</p>
                    <p class="text-xs text-calm-600 truncate">{{ auth()->user()->email }}</p>
                </div>
            </div>
        </div>
    </aside>

    <main class="flex-1 flex flex-col h-full overflow-hidden">
        {{ $slot }}
    </main>

    @livewireScripts
</body>
</html>
