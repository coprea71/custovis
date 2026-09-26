@can('system.maintain')
    @php($update = app(\App\Services\Update\UpdateChecker::class)->availableUpdate())
    @if ($update && ! request()->routeIs('admin.system.migrate'))
        <div role="status" class="bg-amber-50 border-b border-amber-200 px-4 md:px-6 py-2 text-sm text-amber-900">
            Neue Version {{ $update['version'] }} verfügbar (installiert: {{ config('custovis.version') }}).
            <a href="{{ route('admin.system.migrate') }}" class="font-medium underline">Jetzt aktualisieren</a>
        </div>
    @endif
@endcan
