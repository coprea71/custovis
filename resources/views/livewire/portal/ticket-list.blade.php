<div class="space-y-4">
    <div class="flex items-center justify-between">
        <h1 class="text-xl font-semibold text-slatecalm-900">Meine Anfragen</h1>
        @if (app(\App\Services\ModuleAccess::class)->enabled('service-catalog'))
            <a href="{{ route('portal.requests.create') }}" class="px-4 py-2 bg-calm-600 hover:bg-calm-700 text-white rounded-xl text-sm font-medium">Neue Anfrage</a>
        @endif
    </div>

    <div class="bg-white border border-slatecalm-200 rounded-2xl divide-y divide-slatecalm-200">
        @forelse ($tickets as $ticket)
            <a wire:key="t-{{ $ticket->id }}" href="{{ route('portal.tickets.show', $ticket) }}" class="p-4 flex items-center justify-between gap-3 hover:bg-slatecalm-50">
                <div class="min-w-0">
                    <p class="font-medium text-slatecalm-900 truncate">#{{ $ticket->id }} {{ $ticket->subject }}</p>
                    <p class="text-xs text-slate-400">Erstellt am {{ $ticket->created_at->format('d.m.Y') }}</p>
                </div>
                <x-portal.status-badge :status="$ticket->status" />
            </a>
        @empty
            <p class="p-4 text-sm text-slate-400">Sie haben noch keine Anfragen gestellt.</p>
        @endforelse
    </div>

    {{ $tickets->links() }}
</div>
