<div wire:poll.10s>
    @if ($others !== [])
        <div class="flex items-center gap-2 text-xs text-calm-700">
            <span class="w-2 h-2 rounded-full bg-calm-500"></span>
            <span>{{ implode(', ', $others) }} {{ count($others) === 1 ? 'schaut' : 'schauen' }} sich dieses Ticket ebenfalls an</span>
        </div>
    @endif
</div>
