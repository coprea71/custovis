<div class="space-y-4">
    <div class="flex items-baseline justify-between">
        <h1 class="text-xl font-semibold text-slatecalm-900">Management-Dashboard</h1>
        <p class="text-xs text-slate-400">Stand: {{ $snapshot->generated_at->format('d.m.Y H:i') }} Uhr (stündlich aktualisiert)</p>
    </div>

    @if ($failingMailboxCount > 0)
        <div class="flex items-center justify-between gap-4 p-4 rounded-2xl border border-red-200 bg-red-50 text-sm text-red-800">
            <p>⚠ Bei {{ $failingMailboxCount }} {{ $failingMailboxCount === 1 ? 'Mailbox' : 'Mailboxen' }} schlägt der Mailabruf fehl – eingehende E-Mails werden derzeit nicht zu Tickets.</p>
            <a href="{{ route('admin.mailboxes.index') }}" class="shrink-0 font-medium underline hover:no-underline">Zu den Mailboxen</a>
        </div>
    @endif

    <x-dashboard.overview :data="$snapshot->data" />

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <x-dashboard.breakdown title="Offene Tickets je Team" :items="$snapshot->data['open_by_team']" />
        <x-dashboard.breakdown title="Feedback Wissensdatenbank"
                               :items="['Hilfreich' => $snapshot->data['kb_feedback']['helpful'] ?? 0, 'Nicht hilfreich' => $snapshot->data['kb_feedback']['not_helpful'] ?? 0]" />
    </div>
</div>
