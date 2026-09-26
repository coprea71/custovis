<div class="space-y-6">
    <h1 class="text-xl font-semibold text-slatecalm-900">System</h1>

    @if ($output)
        <p class="text-sm rounded-xl px-4 py-3 bg-calm-50 text-calm-800">{{ $output }}</p>
    @endif

    <section class="bg-white border border-slatecalm-200 rounded-2xl p-6 space-y-4">
        <div>
            <h2 class="font-semibold text-slatecalm-900">Programm-Updates</h2>
            <p class="text-sm text-slate-500">Installiert: Version {{ $version }}.
                @if ($lastCheck) Zuletzt geprüft {{ $lastCheck->diffForHumans() }}. @endif
            </p>
        </div>

        @if ($update)
            <div class="rounded-xl bg-amber-50 px-4 py-3 space-y-2">
                <p class="text-sm text-amber-900">Version {{ $update['version'] }} ist verfügbar. <a href="{{ $update['url'] }}" target="_blank" rel="noopener" class="underline">Änderungen ansehen</a></p>
                <p class="text-xs text-amber-800">Vorher ein Backup von Datenbank und Dateien anlegen. Das Update lädt das Release-Archiv von GitHub, prüft die Prüfsumme, überschreibt die Programmdateien (nicht <code>.env</code> und <code>storage/</code>) und führt danach die Migrationen aus.</p>
            </div>
            <button type="button" wire:click="installUpdate" wire:loading.attr="disabled" wire:confirm="Version {{ $update['version'] }} jetzt installieren? Bitte vorher ein Backup anlegen." class="px-4 py-2 bg-calm-600 hover:bg-calm-700 text-white rounded-xl text-sm font-medium disabled:opacity-50">
                <span wire:loading.remove wire:target="installUpdate">Jetzt auf {{ $update['version'] }} aktualisieren</span>
                <span wire:loading wire:target="installUpdate">Update läuft … bitte warten</span>
            </button>
        @else
            <p class="text-sm text-calm-700">Keine neuere Version bekannt.</p>
        @endif

        <button type="button" wire:click="checkForUpdate" wire:loading.attr="disabled" class="px-4 py-2 bg-slatecalm-100 hover:bg-slatecalm-200 text-slate-700 rounded-xl text-sm font-medium">Jetzt auf Updates prüfen</button>
    </section>

    <section class="bg-white border border-slatecalm-200 rounded-2xl p-6 space-y-4">
        <div>
            <h2 class="font-semibold text-slatecalm-900">Datenbank-Updates</h2>
            <p class="text-sm text-slate-500">Nach dem Hochladen einer neuen Version per FTP/SFTP hier die Migrationen ausführen – kein Shell-Zugriff nötig. Vorher ein Datenbank-Backup anlegen.</p>
        </div>

        @if ($pending === [])
            <p class="text-sm text-calm-700">Die Datenbank ist auf dem aktuellen Stand.</p>
        @else
            <ul class="text-xs font-mono text-slate-600 space-y-1">
                @foreach ($pending as $migration)<li>{{ $migration }}</li>@endforeach
            </ul>
            <button type="button" wire:click="migrate" wire:confirm="Ausstehende Migrationen jetzt ausführen?" class="px-4 py-2 bg-calm-600 hover:bg-calm-700 text-white rounded-xl text-sm font-medium">{{ count($pending) }} Migration(en) ausführen</button>
        @endif
    </section>

    <section class="bg-white border border-slatecalm-200 rounded-2xl p-6 space-y-4">
        <div>
            <h2 class="font-semibold text-slatecalm-900">Web-Cron</h2>
            <p class="text-sm text-slate-500">Ohne Cronjob auf dem Server werden keine Mails abgerufen und keine Hintergrundaufgaben erledigt. Lass diese URL beim Hoster oder einem Web-Cron-Dienst jede Minute aufrufen.</p>
        </div>

        @if ($cronUrl)
            <div class="rounded-xl bg-amber-50 px-4 py-3 space-y-2">
                <p class="text-xs text-amber-800">Die URL wird nur jetzt angezeigt. Bitte sofort kopieren und geheim halten.</p>
                <input type="text" readonly value="{{ $cronUrl }}" onclick="this.select()" class="w-full font-mono text-xs border border-amber-200 rounded-lg px-3 py-2 bg-white">
            </div>
        @endif

        <p class="text-sm {{ $cronLastRun ? 'text-calm-700' : 'text-slate-500' }}">
            @if ($cronLastRun)
                Letzter Aufruf: {{ $cronLastRun->diffForHumans() }}
            @elseif ($cronConfigured)
                Die URL wurde erzeugt, aber noch nie aufgerufen.
            @else
                Noch keine Web-Cron-URL erzeugt.
            @endif
        </p>

        <button type="button" wire:click="regenerateCronUrl"
                @if ($cronConfigured) wire:confirm="Neue URL erzeugen? Die bisherige URL funktioniert danach nicht mehr." @endif
                class="px-4 py-2 bg-slatecalm-100 hover:bg-slatecalm-200 text-slate-700 rounded-xl text-sm font-medium">
            {{ $cronConfigured ? 'Neue URL erzeugen' : 'URL erzeugen' }}
        </button>
    </section>
</div>
