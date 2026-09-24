<div class="flex-1 flex overflow-hidden" wire:poll.30s>
    <section class="w-full md:w-72 shrink-0 border-r border-slatecalm-200 bg-white flex flex-col overflow-y-auto">
        <div class="p-4 border-b border-slatecalm-200">
            <h1 class="text-base font-semibold text-slatecalm-900">Team-Chat</h1>
        </div>

        <h2 class="px-4 pt-4 pb-1 text-xs font-semibold text-slate-500 uppercase tracking-wide">Kanäle</h2>
        @foreach ($channels as [$item, $unread])
            <button type="button" wire:key="ch-{{ $item->id }}" wire:click="openChannel({{ $item->id }})"
                    class="mx-2 px-3 py-2 rounded-lg text-sm text-left flex justify-between items-center {{ $conversation instanceof \App\Models\ChatChannel && $conversation->id === $item->id ? 'bg-calm-100 text-calm-800' : 'text-slate-600 hover:bg-slatecalm-100' }}">
                <span class="truncate"># {{ $item->name }}</span>
                @if ($unread > 0)
                    <span class="ml-2 text-[11px] px-1.5 rounded-full bg-calm-600 text-white">{{ $unread }}</span>
                @endif
            </button>
        @endforeach

        <h2 class="px-4 pt-4 pb-1 text-xs font-semibold text-slate-500 uppercase tracking-wide">Direktnachrichten</h2>
        @foreach ($threads as [$item, $unread])
            <button type="button" wire:key="dm-{{ $item->id }}" wire:click="openDirect({{ $item->id }})"
                    class="mx-2 px-3 py-2 rounded-lg text-sm text-left flex justify-between items-center {{ $conversation instanceof \App\Models\ChatDirectThread && $conversation->id === $item->id ? 'bg-calm-100 text-calm-800' : 'text-slate-600 hover:bg-slatecalm-100' }}">
                <span class="truncate">{{ $item->participants->where('id', '!=', auth()->id())->pluck('name')->join(', ') }}</span>
                @if ($unread > 0)
                    <span class="ml-2 text-[11px] px-1.5 rounded-full bg-calm-600 text-white">{{ $unread }}</span>
                @endif
            </button>
        @endforeach

        @if ($directCandidates->isNotEmpty())
            <div class="p-4 mt-auto border-t border-slatecalm-200 flex gap-1.5">
                <select wire:model="newDirectUserId" class="flex-1 border border-slatecalm-200 rounded-lg px-2 py-1 text-xs">
                    <option value="">Neue Direktnachricht an...</option>
                    @foreach ($directCandidates as $candidate)
                        <option value="{{ $candidate->id }}">{{ $candidate->name }}</option>
                    @endforeach
                </select>
                <button type="button" wire:click="startDirect" class="text-xs px-2.5 py-1 rounded-lg bg-calm-600 text-white">Start</button>
            </div>
        @endif
    </section>

    <section class="flex-1 flex flex-col overflow-hidden">
        @if ($conversation)
            <div class="h-14 px-6 flex items-center border-b border-slatecalm-200 bg-white">
                <p class="font-semibold text-slatecalm-900">
                    @if ($conversation instanceof \App\Models\ChatChannel)
                        # {{ $conversation->name }}
                        @if ($conversation->type === 'ticket')
                            <a href="{{ route('agent.tickets.show', $conversation->ticket_id) }}" class="ml-2 text-xs text-ocean-700 hover:underline">zum Ticket</a>
                        @endif
                    @else
                        {{ $conversation->participants->where('id', '!=', auth()->id())->pluck('name')->join(', ') }}
                    @endif
                </p>
            </div>

            <div class="flex-1 overflow-y-auto p-6 space-y-3">
                @forelse ($messages as $message)
                    <div wire:key="msg-{{ $message->id }}">
                        <p class="text-xs text-slate-400">
                            <span class="font-medium text-slatecalm-900">{{ $message->author?->name ?? 'Gelöschter Nutzer' }}</span>
                            · {{ $message->created_at->format('d.m. H:i') }}
                        </p>
                        @if ($message->body)
                            <p class="text-sm text-slate-700 whitespace-pre-line">{{ $message->body }}</p>
                        @endif
                        @if ($message->attachment_path)
                            <a href="{{ route('agent.chat.attachment', $message) }}" class="text-xs text-ocean-700 hover:underline">📎 {{ $message->attachment_name }}</a>
                        @endif
                    </div>
                @empty
                    <p class="text-sm text-slate-400">Noch keine Nachrichten.</p>
                @endforelse
            </div>

            @if ($canPost)
                <form wire:submit="send" class="p-4 border-t border-slatecalm-200 bg-white space-y-2">
                    <textarea wire:model="body" rows="2" placeholder="Nachricht schreiben..." class="w-full border border-slatecalm-200 rounded-xl px-3 py-2 text-sm"></textarea>
                    @error('body') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                    @error('file') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                    <div class="flex items-center justify-between gap-2">
                        <input type="file" wire:model="file" class="text-xs text-slate-500">
                        <button type="submit" class="py-2 px-5 bg-calm-600 hover:bg-calm-700 text-white font-medium rounded-xl text-sm">Senden</button>
                    </div>
                </form>
            @else
                <p class="p-4 border-t border-slatecalm-200 bg-white text-xs text-slate-400">In diesem Kanal hast du nur Leserechte.</p>
            @endif
        @else
            <div class="flex-1 flex items-center justify-center text-slate-400 text-sm">Kanal oder Direktnachricht auswählen.</div>
        @endif
    </section>
</div>
