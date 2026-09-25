@props(['data'])
{{-- Tiles shared by the management and the team dashboard (7.md, DRY). --}}
@php
    $typeLabels = ['support_ticket' => 'Support-Ticket', 'incident' => 'Incident', 'problem' => 'Problem', 'change' => 'Change', 'service_request' => 'Service-Request'];
    $priorityLabels = \App\Models\Ticket::PRIORITY_LABELS;
    $percent = fn (?float $rate) => $rate === null ? '–' : number_format($rate, 1, ',', '.').' %';
@endphp
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
    <x-dashboard.kpi label="Offene Tickets" :value="array_sum($data['open_by_type'])" />
    <x-dashboard.kpi label="Überfällig" :value="array_sum($data['overdue_by_type'])" hint="SLA verletzt oder Lösungsfrist überschritten" />
    <x-dashboard.kpi label="SLA-Compliance" :value="$percent($data['sla_compliance']['rate'])" :hint="$data['sla_compliance']['total'].' Tickets mit SLA, letzte 30 Tage'" />
    <x-dashboard.kpi label="Change-Erfolgsrate" :value="$percent($data['change_success']['rate'])" :hint="$data['change_success']['total'].' abgeschlossene/abgelehnte Changes'" />
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
    <div class="lg:col-span-2">
        <x-dashboard.volume :series="$data['volume']" />
    </div>
    <x-dashboard.kpi label="KI-Nutzung (Monat)"
                     :value="number_format($data['ai_usage']['cost_cents'] / 100, 2, ',', '.').' €'"
                     :hint="number_format($data['ai_usage']['tokens'], 0, ',', '.').' Tokens'" />
</div>

<div class="grid grid-cols-1 md:grid-cols-3 gap-4">
    <x-dashboard.breakdown title="Offen nach Typ" :items="$data['open_by_type']" :labels="$typeLabels" />
    <x-dashboard.breakdown title="Überfällig nach Typ" :items="$data['overdue_by_type']" :labels="$typeLabels" />
    <x-dashboard.breakdown title="Offen nach Priorität" :items="$data['open_by_priority']" :labels="$priorityLabels" />
</div>
