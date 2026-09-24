<x-layouts.app title="Passwort festlegen" body-class="flex items-center justify-center px-4" :scripts="false">
    <div class="w-full max-w-sm bg-white border border-slatecalm-200 rounded-2xl p-8 shadow-sm">
        <h1 class="text-lg font-semibold text-slatecalm-900 mb-6">Neues Passwort festlegen</h1>

        @if ($errors->any())
            <div class="mb-4 text-sm text-red-600">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('password.update') }}" class="space-y-4">
            @csrf
            <input type="hidden" name="token" value="{{ $request->route('token') }}">
            <div>
                <label for="email" class="text-xs font-medium text-slate-600">E-Mail</label>
                <input id="email" type="email" name="email" value="{{ old('email', $request->email) }}" required autocomplete="username"
                       class="w-full mt-1 border border-slatecalm-200 rounded-xl px-3 py-2 text-sm">
            </div>
            <div>
                <label for="password" class="text-xs font-medium text-slate-600">Neues Passwort</label>
                <input id="password" type="password" name="password" required autocomplete="new-password"
                       class="w-full mt-1 border border-slatecalm-200 rounded-xl px-3 py-2 text-sm">
            </div>
            <div>
                <label for="password_confirmation" class="text-xs font-medium text-slate-600">Passwort wiederholen</label>
                <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password"
                       class="w-full mt-1 border border-slatecalm-200 rounded-xl px-3 py-2 text-sm">
            </div>
            <button type="submit" class="w-full py-2.5 bg-calm-600 hover:bg-calm-700 text-white font-medium rounded-xl text-sm">Passwort speichern</button>
        </form>
    </div>
</x-layouts.app>
