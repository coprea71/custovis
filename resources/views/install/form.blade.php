<x-layouts.app title="Installation" body-class="bg-slatecalm-50" :scripts="false">
    <main class="max-w-2xl mx-auto px-4 py-10 space-y-6">
        <div>
            <h1 class="text-2xl font-semibold text-slatecalm-900">{{ config('app.name') }} installieren</h1>
            <p class="text-sm text-slate-500">Einmaliger Einrichtungsassistent. Nach Abschluss wird er dauerhaft deaktiviert.</p>
        </div>

        <section class="bg-white border border-slatecalm-200 rounded-2xl p-6">
            <h2 class="font-semibold text-slatecalm-900 mb-3">Systemvoraussetzungen</h2>
            <ul class="grid grid-cols-1 sm:grid-cols-2 gap-1 text-sm">
                @foreach ($requirements as $label => $ok)
                    <li class="{{ $ok ? 'text-calm-700' : 'text-red-600 font-medium' }}">{{ $ok ? '✓' : '✗' }} {{ $label }}</li>
                @endforeach
            </ul>
        </section>

        @if ($errors->any())
            <div class="rounded-xl px-4 py-3 bg-red-50 text-red-700 text-sm">
                <ul class="list-disc pl-5">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
        @endif

        <form method="POST" action="{{ url('/install') }}" class="bg-white border border-slatecalm-200 rounded-2xl p-6 space-y-6">
            <fieldset class="space-y-3">
                <legend class="font-semibold text-slatecalm-900">Anwendung</legend>
                <label class="block text-sm">Adresse der Installation (URL)
                    <input name="app_url" type="url" required value="{{ $old['app_url'] ?? request()->getSchemeAndHttpHost() }}" class="w-full mt-1 border border-slatecalm-200 rounded-xl px-3 py-2">
                </label>
            </fieldset>

            <fieldset class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <legend class="font-semibold text-slatecalm-900 sm:col-span-2">MySQL/MariaDB-Datenbank</legend>
                <label class="block text-sm">Host<input name="db_host" required value="{{ $old['db_host'] ?? 'localhost' }}" class="w-full mt-1 border border-slatecalm-200 rounded-xl px-3 py-2"></label>
                <label class="block text-sm">Port<input name="db_port" type="number" required value="{{ $old['db_port'] ?? 3306 }}" class="w-full mt-1 border border-slatecalm-200 rounded-xl px-3 py-2"></label>
                <label class="block text-sm">Datenbankname<input name="db_database" required value="{{ $old['db_database'] ?? '' }}" class="w-full mt-1 border border-slatecalm-200 rounded-xl px-3 py-2"></label>
                <label class="block text-sm">Benutzer<input name="db_username" required value="{{ $old['db_username'] ?? '' }}" autocomplete="off" class="w-full mt-1 border border-slatecalm-200 rounded-xl px-3 py-2"></label>
                <label class="block text-sm sm:col-span-2">Passwort<input name="db_password" type="password" autocomplete="off" class="w-full mt-1 border border-slatecalm-200 rounded-xl px-3 py-2"></label>
            </fieldset>

            <fieldset class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <legend class="font-semibold text-slatecalm-900 sm:col-span-2">Erster Administrator</legend>
                <label class="block text-sm">Name<input name="admin_name" required value="{{ $old['admin_name'] ?? '' }}" class="w-full mt-1 border border-slatecalm-200 rounded-xl px-3 py-2"></label>
                <label class="block text-sm">E-Mail<input name="admin_email" type="email" required value="{{ $old['admin_email'] ?? '' }}" class="w-full mt-1 border border-slatecalm-200 rounded-xl px-3 py-2"></label>
                <label class="block text-sm">Passwort (mind. 12 Zeichen)<input name="admin_password" type="password" required minlength="12" autocomplete="new-password" class="w-full mt-1 border border-slatecalm-200 rounded-xl px-3 py-2"></label>
                <label class="block text-sm">Passwort wiederholen<input name="admin_password_confirmation" type="password" required autocomplete="new-password" class="w-full mt-1 border border-slatecalm-200 rounded-xl px-3 py-2"></label>
            </fieldset>

            <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="demo_data" value="1" @checked($old['demo_data'] ?? false)> Demo-Daten anlegen (Teams, Tickets, Wissensdatenbank …)</label>

            <div class="flex justify-end">
                <button type="submit" class="px-6 py-2.5 bg-calm-600 hover:bg-calm-700 text-white rounded-xl text-sm font-medium">Installieren</button>
            </div>
        </form>
    </main>
</x-layouts.app>
