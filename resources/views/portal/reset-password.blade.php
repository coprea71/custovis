<x-portal.auth-card title="Passwort festlegen" subtitle="Legen Sie ein neues Passwort mit mindestens 12 Zeichen fest.">
    <form method="POST" action="{{ route('portal.password.update') }}" class="space-y-4">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <div>
            <label for="email" class="text-xs font-medium text-slate-600">E-Mail</label>
            <input id="email" type="email" name="email" value="{{ old('email', $email) }}" required autocomplete="username"
                   class="w-full mt-1 border border-slatecalm-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-calm-400">
        </div>
        <div>
            <label for="password" class="text-xs font-medium text-slate-600">Neues Passwort</label>
            <input id="password" type="password" name="password" required minlength="12" autofocus autocomplete="new-password"
                   class="w-full mt-1 border border-slatecalm-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-calm-400">
        </div>
        <div>
            <label for="password_confirmation" class="text-xs font-medium text-slate-600">Passwort wiederholen</label>
            <input id="password_confirmation" type="password" name="password_confirmation" required minlength="12" autocomplete="new-password"
                   class="w-full mt-1 border border-slatecalm-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-calm-400">
        </div>
        <button type="submit" class="w-full py-2.5 bg-calm-600 hover:bg-calm-700 text-white font-medium rounded-xl shadow-sm text-sm">Passwort speichern</button>
    </form>
</x-portal.auth-card>
