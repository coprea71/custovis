<x-layouts.app title="Passwort bestätigen" body-class="flex items-center justify-center px-4" :scripts="false">
    <div class="w-full max-w-sm bg-white border border-slatecalm-200 rounded-2xl p-8 shadow-sm">
        <h1 class="text-lg font-semibold text-slatecalm-900 mb-2">Passwort bestätigen</h1>
        <p class="text-sm text-slate-500 mb-6">Für diese Sicherheitseinstellung bestätigen Sie bitte Ihr Passwort.</p>

        @if ($errors->any())
            <div class="mb-4 text-sm text-red-600">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('password.confirm.store') }}" class="space-y-4">
            @csrf
            <div>
                <label for="password" class="text-xs font-medium text-slate-600">Passwort</label>
                <input id="password" type="password" name="password" required autofocus autocomplete="current-password"
                       class="w-full mt-1 border border-slatecalm-200 rounded-xl px-3 py-2 text-sm">
            </div>
            <button type="submit" class="w-full py-2.5 bg-calm-600 hover:bg-calm-700 text-white font-medium rounded-xl text-sm">Bestätigen</button>
        </form>
    </div>
</x-layouts.app>
