<div class="space-y-6">
    <h1 class="text-xl font-semibold text-slatecalm-900">System</h1>

    <section class="bg-white border border-slatecalm-200 rounded-2xl p-6 space-y-4">
        <div>
            <h2 class="font-semibold text-slatecalm-900">Datenbank-Updates</h2>
            <p class="text-sm text-slate-500">Version {{ $version }}. Nach dem Hochladen einer neuen Version per FTP/SFTP hier die Migrationen ausführen – kein Shell-Zugriff nötig. Vorher ein Datenbank-Backup anlegen.</p>
        </div>

        @if ($output)
            <p class="text-sm rounded-xl px-4 py-3 bg-calm-50 text-calm-800">{{ $output }}</p>
        @endif

        @if ($pending === [])
            <p class="text-sm text-calm-700">Die Datenbank ist auf dem aktuellen Stand.</p>
        @else
            <ul class="text-xs font-mono text-slate-600 space-y-1">
                @foreach ($pending as $migration)<li>{{ $migration }}</li>@endforeach
            </ul>
            <button type="button" wire:click="migrate" wire:confirm="Ausstehende Migrationen jetzt ausführen?" class="px-4 py-2 bg-calm-600 hover:bg-calm-700 text-white rounded-xl text-sm font-medium">{{ count($pending) }} Migration(en) ausführen</button>
        @endif
    </section>
</div>
