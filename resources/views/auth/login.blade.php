<!DOCTYPE html>
<html lang="de" class="h-full bg-[#F4F7F6]">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name') }} — Anmelden</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css'])
</head>
<body class="h-full font-sans text-slate-700 antialiased flex items-center justify-center">
    <div class="w-full max-w-sm bg-white border border-slatecalm-200 rounded-2xl p-8 shadow-sm">
        <div class="w-10 h-10 rounded-xl bg-calm-600 flex items-center justify-center text-white font-bold mb-4">C</div>
        <h1 class="text-lg font-semibold text-slatecalm-900 mb-6">{{ config('app.name') }}</h1>

        @if ($errors->any())
            <div class="mb-4 text-sm text-red-600">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('login') }}" class="space-y-4">
            @csrf
            <div>
                <label class="text-xs font-medium text-slate-600">E-Mail</label>
                <input type="email" name="email" value="{{ old('email') }}" required autofocus
                       class="w-full mt-1 border border-slatecalm-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-calm-400">
            </div>
            <div>
                <label class="text-xs font-medium text-slate-600">Passwort</label>
                <input type="password" name="password" required
                       class="w-full mt-1 border border-slatecalm-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-calm-400">
            </div>
            <button type="submit"
                    class="w-full py-2.5 bg-calm-600 hover:bg-calm-700 text-white font-medium rounded-xl shadow-sm text-sm">
                Anmelden
            </button>
        </form>
    </div>
</body>
</html>
