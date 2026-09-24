<div class="space-y-4">
    <a href="{{ route('portal.tickets.index') }}" class="text-sm text-slate-500 hover:text-calm-700">&larr; Meine Anfragen</a>

    <div class="bg-white border border-slatecalm-200 rounded-2xl p-6">
        <div class="flex items-start justify-between gap-3">
            <h1 class="text-xl font-semibold text-slatecalm-900">#{{ $ticket->id }} {{ $ticket->subject }}</h1>
            <x-portal.status-badge :status="$ticket->status" />
        </div>

        <ol class="mt-6 relative border-l border-slatecalm-200 ml-2 space-y-6">
            @foreach ($timeline as $entry)
                <li class="ml-5">
                    <span class="absolute -left-1.5 mt-1.5 w-3 h-3 rounded-full bg-calm-500 border-2 border-white"></span>
                    <p class="text-xs text-slate-400">{{ $entry['at']->format('d.m.Y H:i') }} · {{ $entry['label'] }}</p>
                    @if ($entry['body'])
                        <p class="mt-1 text-sm text-slate-700 whitespace-pre-line">{{ $entry['body'] }}</p>
                    @endif
                </li>
            @endforeach
        </ol>
    </div>

    <form wire:submit="sendReply" class="bg-white border border-slatecalm-200 rounded-2xl p-6 space-y-3">
        <label for="reply" class="text-sm font-medium text-slatecalm-900">Nachricht an den Support</label>
        <textarea id="reply" wire:model="reply" rows="4" class="w-full border border-slatecalm-200 rounded-xl px-3 py-2 text-sm"></textarea>
        @error('reply') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
        <div class="flex justify-end">
            <button type="submit" class="px-5 py-2 bg-calm-600 hover:bg-calm-700 text-white rounded-xl text-sm font-medium">Senden</button>
        </div>
    </form>
</div>
