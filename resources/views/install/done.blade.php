<x-layouts.app title="Installation abgeschlossen" body-class="flex items-center justify-center px-4" :scripts="false">
    <div class="w-full max-w-md bg-white border border-slatecalm-200 rounded-2xl p-8 shadow-sm space-y-4">
        <h1 class="text-lg font-semibold text-slatecalm-900">Installation abgeschlossen</h1>
        <p class="text-sm text-slate-600">Der Installationsassistent ist jetzt dauerhaft deaktiviert. Richten Sie nach der ersten Anmeldung die Zwei-Faktor-Authentifizierung ein – sie ist für alle Agenten und Administratoren verpflichtend.</p>
        <p class="text-sm text-slate-600">Für Schema-Updates späterer Versionen: Dateien per FTP hochladen und unter <strong>Administration → System</strong> die Migrationen ausführen.</p>
        <a href="{{ $loginUrl }}" class="inline-block px-5 py-2.5 bg-calm-600 hover:bg-calm-700 text-white rounded-xl text-sm font-medium">Zur Anmeldung</a>
    </div>
</x-layouts.app>
