<x-layouts.app title="Kundenportal — Anmelden" body-class="flex items-center justify-center px-4" :scripts="false">
    <div class="w-full max-w-sm bg-white border border-slatecalm-200 rounded-2xl p-8 shadow-sm">
        <div class="w-10 h-10 rounded-xl bg-calm-600 flex items-center justify-center text-white font-bold mb-4">C</div>
        <h1 class="text-lg font-semibold text-slatecalm-900">Kundenportal</h1>
        <p class="text-sm text-slate-500 mb-6">Anfragen stellen, Status verfolgen, Hilfe-Artikel lesen.</p>

        @if ($errors->any())
            <div class="mb-4 text-sm text-red-600">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('portal.login.store') }}" class="space-y-4">
            @csrf
            <div>
                <label for="email" class="text-xs font-medium text-slate-600">E-Mail</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username"
                       class="w-full mt-1 border border-slatecalm-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-calm-400">
            </div>
            <div>
                <label for="password" class="text-xs font-medium text-slate-600">Passwort</label>
                <input id="password" type="password" name="password" required autocomplete="current-password"
                       class="w-full mt-1 border border-slatecalm-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-calm-400">
            </div>
            <button type="submit" class="w-full py-2.5 bg-calm-600 hover:bg-calm-700 text-white font-medium rounded-xl shadow-sm text-sm">Anmelden</button>
        </form>
    </div>
</x-layouts.app>
