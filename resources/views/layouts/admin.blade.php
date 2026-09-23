<!DOCTYPE html>
<html lang="de" class="h-full bg-[#F4F7F6]">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name') }} — Administration</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="h-full font-sans text-slate-700 antialiased bg-slatecalm-50">
    <header class="h-16 bg-white border-b border-slatecalm-200 px-6 flex items-center">
        <span class="font-semibold text-slatecalm-900">{{ config('app.name') }} — Administration</span>
    </header>

    <main class="p-6 max-w-5xl mx-auto">
        {{ $slot }}
    </main>

    @livewireScripts
</body>
</html>
