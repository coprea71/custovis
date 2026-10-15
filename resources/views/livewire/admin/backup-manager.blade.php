<div class="space-y-6">
    <h1 class="text-xl font-semibold text-slatecalm-900">Datensicherung</h1>

    @if ($output)
        <p class="text-sm rounded-xl px-4 py-3 bg-calm-50 text-calm-800" role="status">{{ $output }}</p>
    @endif

    <section class="bg-white border border-slatecalm-200 rounded-2xl p-6 space-y-4">
        <div>
            <h2 class="font-semibold text-slatecalm-900">Sicherung erstellen</h2>
            <p class="text-sm text-slate-500">Sichert die komplette Datenbank als verschlüsselte ZIP-Datei (AES-256). Das Passwort steht in einer eigenen Textdatei – beide Dateien getrennt aufbewahren. Hochgeladene Dateianhänge sind nicht enthalten. Zum Öffnen außerhalb von Custovis z. B. 7-Zip verwenden.</p>
        </div>

        <p class="text-sm {{ $overdue ? 'text-amber-800' : 'text-calm-700' }}">
            @if ($lastBackupAt)
                Letzte Sicherung am {{ $lastBackupAt->timezone(config('app.timezone'))->format('d.m.Y H:i') }} Uhr.
            @else
                Es wurde noch keine Sicherung erstellt.
            @endif
            @if ($overdue) Bitte alle {{ \App\Services\Backup\BackupService::REMINDER_DAYS }} Tage sichern. @endif
        </p>

        <button type="button" wire:click="create" wire:loading.attr="disabled" class="px-4 py-2 bg-calm-600 hover:bg-calm-700 text-white rounded-xl text-sm font-medium disabled:opacity-50">
            <span wire:loading.remove wire:target="create">Sicherung erstellen</span>
            <span wire:loading wire:target="create">Sicherung läuft … bitte warten</span>
        </button>

        @if ($backup)
            <div class="rounded-xl bg-slatecalm-50 px-4 py-3 space-y-3">
                <p class="text-sm text-slatecalm-900">Sicherung vom {{ $backup['created_at']->timezone(config('app.timezone'))->format('d.m.Y H:i') }} Uhr liegt auf dem Server bereit.</p>
                <div class="flex flex-wrap gap-2">
                    <button type="button" wire:click="downloadArchive" class="px-4 py-2 bg-calm-600 hover:bg-calm-700 text-white rounded-xl text-sm font-medium">ZIP-Datei herunterladen</button>
                    <button type="button" wire:click="downloadPassword" class="px-4 py-2 bg-slatecalm-100 hover:bg-slatecalm-200 text-slate-700 rounded-xl text-sm font-medium">Passwort-Datei herunterladen</button>
                    <button type="button" wire:click="deleteStored" wire:confirm="Sicherung vom Server löschen? Vorher beide Dateien herunterladen." class="px-4 py-2 text-red-700 hover:bg-red-50 rounded-xl text-sm font-medium">Vom Server löschen</button>
                </div>
                <p class="text-xs text-slate-500">Nach dem Herunterladen vom Server löschen – die Sicherung enthält personenbezogene Daten.</p>
            </div>
        @endif
    </section>

    <section class="bg-white border border-slatecalm-200 rounded-2xl p-6 space-y-4">
        <div>
            <h2 class="font-semibold text-slatecalm-900">Automatische Sicherung</h2>
            <p class="text-sm text-slate-500">Legt einmal täglich per Cron oder Web-Cron eine Sicherung (ZIP und Passwort-Datei) im angegebenen Server-Verzeichnis ab und behält nur die neuesten. Das Verzeichnis sollte außerhalb des Programmordners liegen und regelmäßig vom Server weg kopiert werden.</p>
        </div>

        <p class="text-sm text-slate-600">
            @if ($scheduledLastRunAt)
                Letzte automatische Sicherung am {{ $scheduledLastRunAt->timezone(config('app.timezone'))->format('d.m.Y H:i') }} Uhr.
            @else
                Bisher keine automatische Sicherung.
            @endif
        </p>
        @if ($scheduledError)
            <p class="text-sm rounded-xl px-4 py-3 bg-red-50 text-red-700" role="alert">Letzter Versuch fehlgeschlagen: {{ $scheduledError }}</p>
        @endif

        <form wire:submit="saveSchedule" class="space-y-3">
            <label class="flex items-center gap-2 text-sm text-slate-700">
                <input type="checkbox" wire:model="scheduledEnabled">
                Täglich automatisch sichern
            </label>
            <div>
                <label for="backup-directory" class="text-xs font-medium text-slate-600">Verzeichnis auf dem Server (absoluter Pfad)</label>
                <input id="backup-directory" type="text" wire:model="scheduledDirectory" maxlength="500" placeholder="{{ $defaultDirectory }}" class="w-full mt-1 border border-slatecalm-200 rounded-xl px-3 py-2 text-sm font-mono">
                <p class="text-xs text-slate-400 mt-1">Leer lassen für das Standardverzeichnis.</p>
                @error('scheduledDirectory') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="backup-keep" class="text-xs font-medium text-slate-600">Anzahl aufbewahrter Sicherungen</label>
                <input id="backup-keep" type="number" min="1" max="365" wire:model="scheduledKeep" class="w-32 mt-1 border border-slatecalm-200 rounded-xl px-3 py-2 text-sm">
                @error('scheduledKeep') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>
            <button type="submit" class="px-4 py-2 bg-calm-600 hover:bg-calm-700 text-white rounded-xl text-sm font-medium">Speichern</button>
        </form>
    </section>

    <section class="bg-white border border-slatecalm-200 rounded-2xl p-6 space-y-4">
        <div>
            <h2 class="font-semibold text-slatecalm-900">Sicherung zurückspielen</h2>
            <p class="text-sm text-slate-500">Ersetzt die komplette Datenbank durch den Stand der Sicherung. Alle Änderungen seit der Sicherung gehen verloren, danach ist eine neue Anmeldung nötig. Vorher eine aktuelle Sicherung erstellen.</p>
        </div>

        @if ($restoreError)
            <p class="text-sm rounded-xl px-4 py-3 bg-red-50 text-red-700" role="alert">{{ $restoreError }}</p>
        @endif

        <form method="POST" action="{{ route('admin.backup.restore') }}" enctype="multipart/form-data" class="space-y-3"
              onsubmit="return confirm('Datenbank wirklich durch die Sicherung ersetzen? Das kann nicht rückgängig gemacht werden.')">
            @csrf
            <div>
                <label for="backup-archive" class="text-xs font-medium text-slate-600">ZIP-Datei der Sicherung</label>
                <input id="backup-archive" type="file" name="archive" accept=".zip" required class="block w-full mt-1 text-sm">
            </div>
            <div>
                <label for="backup-password" class="text-xs font-medium text-slate-600">Passwort-Datei (.txt)</label>
                <input id="backup-password" type="file" name="password_file" accept=".txt" required class="block w-full mt-1 text-sm">
            </div>
            <label class="flex items-center gap-2 text-sm text-slate-700">
                <input type="checkbox" name="confirm" value="1" required>
                Mir ist klar, dass die aktuelle Datenbank überschrieben wird.
            </label>
            <button type="submit" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-xl text-sm font-medium">Sicherung zurückspielen</button>
        </form>
    </section>
</div>
