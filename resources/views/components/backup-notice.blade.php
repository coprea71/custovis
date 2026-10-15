@can('system.maintain')
    @if (! request()->routeIs('admin.backup') && app(\App\Services\Backup\BackupService::class)->isOverdue())
        <div role="status" class="shrink-0 bg-amber-50 border-b border-amber-200 px-4 md:px-6 py-2 text-sm text-amber-900">
            Datensicherung fällig: Die letzte Sicherung ist älter als {{ \App\Services\Backup\BackupService::REMINDER_DAYS }} Tage oder fehlt.
            <a href="{{ route('admin.backup') }}" class="font-medium underline">Jetzt sichern</a>
        </div>
    @endif
@endcan
