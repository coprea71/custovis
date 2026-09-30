@props(['title' => null, 'bodyClass' => '', 'scripts' => true])
{{-- Shared base layout for /agent, /admin and /portal (17.md): the active theme is resolved server-side once, here. --}}
<!DOCTYPE html>
<html lang="de" class="h-full bg-canvas" @if ($activeTheme) data-theme="{{ $activeTheme->slug }}" @endif>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ config('app.name') }}{{ $title ? ' — '.$title : '' }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">

    @if ($scripts)
        <script>window.custovisRealtime = @js(\App\Support\Realtime::clientConfig());</script>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @livewireStyles
    @else
        @vite(['resources/css/app.css'])
    @endif
    @if ($themeCss)
        <style>{!! $themeCss !!}</style>
    @endif
    {{ $head ?? '' }}
</head>
<body class="h-full font-sans text-slate-700 antialiased {{ $bodyClass }}">
    {{ $slot }}

    <div class="fixed bottom-1 left-2 z-30 text-[10px] text-slate-400 pointer-events-none select-none" aria-label="Version">v{{ config('custovis.version') }}</div>

    @if ($scripts)
        <x-confirm-dialog />
        @livewireScripts
    @endif
</body>
</html>
