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

    @if ($scripts)
        @livewireScripts
    @endif
</body>
</html>
