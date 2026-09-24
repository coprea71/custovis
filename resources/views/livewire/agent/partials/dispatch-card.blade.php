<div wire:key="appt-{{ $appointment->id }}" draggable="true"
     @dragstart="$event.dataTransfer.setData('text/plain', '{{ $appointment->id }}')"
     class="border border-slatecalm-200 rounded-xl p-3 bg-slatecalm-50 cursor-move text-xs space-y-1">
    <div class="flex justify-between text-slate-500">
        <span>{{ $appointment->scheduled_start->format('H:i') }}–{{ $appointment->scheduled_end->format('H:i') }}</span>
        <span>{{ $appointment->state->label() }}</span>
    </div>
    <p class="font-medium text-slatecalm-900 text-sm">
        <a href="{{ route('agent.tickets.show', $appointment->ticket_id) }}" class="hover:underline">#{{ $appointment->ticket_id }}</a>
        {{ $appointment->ticket->subject }}
    </p>
    <p class="text-slate-500">{{ $appointment->address }}@if ($appointment->kind === 'delivery') · Auslieferung @endif</p>
    <div class="flex gap-2 pt-1">
        <button type="button" wire:click="$set('suggestFor', {{ $appointment->id }})" class="text-ocean-700 hover:underline">Vorschläge</button>
        @if ($appointment->technician_profile_id)
            <button type="button" wire:click="unassign({{ $appointment->id }})" class="text-slate-500 hover:underline">Zurücknehmen</button>
        @endif
        <button type="button" wire:click="cancel({{ $appointment->id }})" wire:confirm="Einsatz stornieren?" class="text-red-600 hover:underline">Stornieren</button>
    </div>
</div>
