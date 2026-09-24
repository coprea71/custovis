<x-layouts.app title="Passwort vergessen" body-class="flex items-center justify-center px-4" :scripts="false">
    <div class="w-full max-w-sm bg-white border border-slatecalm-200 rounded-2xl p-8 shadow-sm">
        <h1 class="text-lg font-semibold text-slatecalm-900 mb-2">Passwort vergessen</h1>
        <p class="text-sm text-slate-500 mb-6">Wir senden Ihnen einen Link, mit dem Sie ein neues Passwort festlegen.</p>

        @if (session('status'))
            <div class="mb-4 text-sm text-calm-700">Falls ein Konto mit dieser Adresse existiert, ist der Link unterwegs.</div>
        @endif
        @if ($errors->any())
            <div class="mb-4 text-sm text-red-600">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
            @csrf
            <div>
                <label for="email" class="text-xs font-medium text-slate-600">E-Mail</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username"
                       class="w-full mt-1 border border-slatecalm-200 rounded-xl px-3 py-2 text-sm">
            </div>
            <button type="submit" class="w-full py-2.5 bg-calm-600 hover:bg-calm-700 text-white font-medium rounded-xl text-sm">Link senden</button>
        </form>
        <a href="{{ route('login') }}" class="block mt-4 text-center text-xs text-slate-500 hover:text-calm-700">Zur Anmeldung</a>
    </div>
</x-layouts.app>
