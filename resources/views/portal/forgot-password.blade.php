<x-portal.auth-card title="Passwort vergessen" subtitle="Geben Sie Ihre E-Mail-Adresse ein. Wir senden Ihnen einen Link zum Festlegen eines neuen Passworts.">
    <form method="POST" action="{{ route('portal.password.email') }}" class="space-y-4">
        @csrf
        <div>
            <label for="email" class="text-xs font-medium text-slate-600">E-Mail</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username"
                   class="w-full mt-1 border border-slatecalm-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-calm-400">
        </div>
        <button type="submit" class="w-full py-2.5 bg-calm-600 hover:bg-calm-700 text-white font-medium rounded-xl shadow-sm text-sm">Link anfordern</button>
    </form>
    <a href="{{ route('portal.login') }}" class="block mt-4 text-center text-xs text-calm-700 hover:underline">Zurück zur Anmeldung</a>
</x-portal.auth-card>
