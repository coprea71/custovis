<x-layouts.app title="Kontosicherheit" body-class="bg-slatecalm-50" :scripts="false">
    <main class="max-w-xl mx-auto px-4 py-10 space-y-6">
        <div class="flex items-center justify-between">
            <h1 class="text-xl font-semibold text-slatecalm-900">Zwei-Faktor-Authentifizierung</h1>
            @if ($user->two_factor_confirmed_at || ! $mandatory)
                <a href="{{ route('agent.tickets.index') }}" class="text-sm text-slate-500 hover:text-calm-700">Zum Agenten-Bereich</a>
            @endif
        </div>

        @if (session('status') === 'two-factor-required')
            <p class="text-sm rounded-xl px-4 py-3 bg-ocean-50 text-ocean-700">Bitte richten Sie die Zwei-Faktor-Authentifizierung ein, bevor Sie fortfahren. Sie ist für alle Agenten und Administratoren verpflichtend.</p>
        @endif
        @if ($errors->any())
            <p class="text-sm rounded-xl px-4 py-3 bg-red-50 text-red-700">{{ $errors->first() }}</p>
        @endif

        <section class="bg-white border border-slatecalm-200 rounded-2xl p-6 space-y-4">
            @if ($user->two_factor_confirmed_at)
                <p class="text-sm text-calm-700 font-medium">Aktiv seit {{ $user->two_factor_confirmed_at->format('d.m.Y') }}.</p>

                <details class="text-sm">
                    <summary class="cursor-pointer text-slate-600">Wiederherstellungscodes anzeigen</summary>
                    <ul class="mt-2 grid grid-cols-2 gap-1 font-mono text-xs">
                        @foreach ($user->recoveryCodes() as $code)<li>{{ $code }}</li>@endforeach
                    </ul>
                    <form method="POST" action="{{ route('two-factor.regenerate-recovery-codes') }}" class="mt-2">
                        @csrf
                        <button class="text-xs text-ocean-700 underline">Neue Codes erzeugen</button>
                    </form>
                </details>

                @unless ($mandatory)
                    <form method="POST" action="{{ route('two-factor.disable') }}">
                        @csrf
                        @method('DELETE')
                        <button class="text-sm px-4 py-2 rounded-xl bg-red-50 text-red-700">Deaktivieren</button>
                    </form>
                @endunless
            @elseif ($pendingConfirmation)
                <p class="text-sm text-slate-600">Scannen Sie den QR-Code mit Ihrer Authenticator-App und bestätigen Sie mit dem angezeigten Code.</p>
                <div class="inline-block bg-white p-2 border border-slatecalm-200 rounded-xl">{!! $user->twoFactorQrCodeSvg() !!}</div>
                <form method="POST" action="{{ route('two-factor.confirm') }}" class="flex gap-2">
                    @csrf
                    <label for="code" class="sr-only">Bestätigungscode</label>
                    <input id="code" name="code" inputmode="numeric" autocomplete="one-time-code" required class="flex-1 border border-slatecalm-200 rounded-xl px-3 py-2 text-sm">
                    <button class="px-4 py-2 bg-calm-600 text-white rounded-xl text-sm font-medium">Bestätigen</button>
                </form>
            @else
                <p class="text-sm text-slate-600">Schützen Sie Ihr Konto mit einem zweiten Faktor (TOTP-App wie FreeOTP, Aegis oder Microsoft Authenticator).</p>
                <form method="POST" action="{{ route('two-factor.enable') }}">
                    @csrf
                    <button class="px-4 py-2 bg-calm-600 text-white rounded-xl text-sm font-medium">Einrichten</button>
                </form>
            @endif
        </section>
    </main>
</x-layouts.app>
