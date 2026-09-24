<div class="flex-1 overflow-y-auto p-6 max-w-3xl space-y-4">
    <h1 class="text-xl font-semibold text-slatecalm-900">CAB-Freigaben</h1>

    @forelse ($approvals as $approval)
        <div wire:key="ap-{{ $approval->id }}" class="bg-white border border-slatecalm-200 rounded-2xl p-5 space-y-3 text-sm">
            <div class="flex justify-between gap-3">
                <a href="{{ route('agent.tickets.show', $approval->ticket_id) }}" class="font-medium text-slatecalm-900 hover:underline">#{{ $approval->ticket_id }} {{ $approval->ticket->subject }}</a>
                <span class="text-xs text-slate-400">angefragt {{ $approval->created_at->diffForHumans() }}</span>
            </div>
            @if ($approval->ticket->change)
                <p class="text-xs text-slate-500">
                    Typ {{ $approval->ticket->change->change_type }} · Risiko {{ $approval->ticket->change->risk_level }}
                    @if ($approval->ticket->change->planned_start) · geplant ab {{ $approval->ticket->change->planned_start->format('d.m.Y H:i') }} @endif
                </p>
            @endif
            <textarea wire:model="comments.{{ $approval->id }}" rows="2" placeholder="Kommentar (optional)" aria-label="Kommentar" class="w-full border border-slatecalm-200 rounded-xl px-3 py-2"></textarea>
            <div class="flex gap-2 justify-end">
                <button type="button" wire:click="decide({{ $approval->id }}, false)" wire:confirm="Change ablehnen? Eine Ablehnung beendet die Freigabe." class="px-4 py-2 rounded-xl bg-red-50 text-red-700 font-medium">Ablehnen</button>
                <button type="button" wire:click="decide({{ $approval->id }}, true)" class="px-4 py-2 rounded-xl bg-calm-600 text-white font-medium">Genehmigen</button>
            </div>
        </div>
    @empty
        <p class="text-sm text-slate-400">Keine offenen Freigaben.</p>
    @endforelse
</div>
