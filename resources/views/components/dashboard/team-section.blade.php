@props(['team', 'snapshot', 'recentTickets', 'heading' => null])

<section class="space-y-4">
    <div class="flex items-baseline justify-between">
        <h2 class="text-xl font-semibold text-slatecalm-900">{{ $heading ?? $team->name }}</h2>
        <p class="text-xs text-slate-400">Stand: {{ $snapshot->generated_at->format('d.m.Y H:i') }} Uhr</p>
    </div>

    <x-dashboard.overview :data="$snapshot->data"
                          :open-tickets-url="route('agent.tickets.index', ['team' => $team->id, 'status' => 'open'])" />

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <x-dashboard.breakdown title="Auslastung je Agent (offene Tickets)" :items="$snapshot->data['agent_load']" />

        <div class="bg-white border border-slatecalm-200 rounded-2xl p-5">
            <h3 class="text-sm font-semibold text-slatecalm-900 mb-3">Letzte Aktivitäten</h3>
            <ul class="divide-y divide-slatecalm-100">
                @forelse ($recentTickets as $ticket)
                    <li class="py-2 flex justify-between gap-3 text-sm">
                        <a href="{{ route('agent.tickets.show', $ticket) }}" class="text-slatecalm-900 hover:text-calm-700 truncate">#{{ $ticket->id }} {{ $ticket->subject }}</a>
                        <span class="text-xs text-slate-400 shrink-0">{{ $ticket->updated_at->diffForHumans() }}</span>
                    </li>
                @empty
                    <li class="py-2 text-sm text-slate-400">Noch keine Tickets.</li>
                @endforelse
            </ul>
        </div>
    </div>
</section>
