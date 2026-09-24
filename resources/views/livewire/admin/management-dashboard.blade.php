<div class="space-y-4">
    <div class="flex items-baseline justify-between">
        <h1 class="text-xl font-semibold text-slatecalm-900">Management-Dashboard</h1>
        <p class="text-xs text-slate-400">Stand: {{ $snapshot->generated_at->format('d.m.Y H:i') }} Uhr (stündlich aktualisiert)</p>
    </div>

    <x-dashboard.overview :data="$snapshot->data" />

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <x-dashboard.breakdown title="Offene Tickets je Team" :items="$snapshot->data['open_by_team']" />
        <x-dashboard.breakdown title="Feedback Wissensdatenbank"
                               :items="['Hilfreich' => $snapshot->data['kb_feedback']['helpful'] ?? 0, 'Nicht hilfreich' => $snapshot->data['kb_feedback']['not_helpful'] ?? 0]" />
    </div>
</div>
