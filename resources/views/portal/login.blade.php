<x-portal.auth-card title="Anmelden" subtitle="Anfragen stellen, Status verfolgen, Hilfe-Artikel lesen.">
    <form method="POST" action="{{ route('portal.login.store') }}" class="space-y-4">
        @csrf
        <div>
            <label for="email" class="text-xs font-medium text-slate-600">E-Mail</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username"
                   class="w-full mt-1 border border-slatecalm-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-calm-400">
        </div>
        <div>
            <div class="flex items-center justify-between">
                <label for="password" class="text-xs font-medium text-slate-600">Passwort</label>
                <a href="{{ route('portal.password.request') }}" class="text-xs text-calm-700 hover:underline">Passwort vergessen?</a>
            </div>
            <input id="password" type="password" name="password" required autocomplete="current-password"
                   class="w-full mt-1 border border-slatecalm-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-calm-400">
        </div>
        <button type="submit" class="w-full py-2.5 bg-calm-600 hover:bg-calm-700 text-white font-medium rounded-xl shadow-sm text-sm">Anmelden</button>
    </form>
</x-portal.auth-card>
