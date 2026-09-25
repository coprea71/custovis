<div class="flex flex-1 h-full overflow-hidden">

    {{-- Ticket list (master) --}}
    <div class="w-full md:w-5/12 lg:w-4/12 border-r border-slatecalm-200 bg-white flex flex-col h-full overflow-hidden">
        <div class="p-4 border-b border-slatecalm-200 bg-slatecalm-50/50 space-y-3">
            <a href="{{ route('agent.tickets.create') }}" class="block w-full text-center py-2 bg-calm-600 hover:bg-calm-700 text-white rounded-xl text-sm font-medium">+ Neues Ticket</a>
            <input
                type="text"
                wire:model.live.debounce.400ms="search"
                placeholder="Tickets oder E-Mail suchen..."
                class="w-full pl-3 pr-4 py-1.5 bg-white border border-slatecalm-200 text-slate-700 text-sm rounded-xl focus:outline-none focus:ring-2 focus:ring-calm-400 transition placeholder-slate-400"
            >

            <div class="flex items-center space-x-2 text-xs overflow-x-auto">
                @foreach (['all' => 'Alle', 'mine' => 'Mir zugewiesen', ...\App\Models\Ticket::STATUS_LABELS] as $value => $label)
                    <button
                        type="button"
                        wire:click="setStatusFilter('{{ $value }}')"
                        class="px-3 py-1 rounded-lg font-medium shrink-0 transition {{ $statusFilter === $value ? 'bg-calm-600 text-white' : 'bg-slatecalm-100 text-slate-600 hover:bg-slatecalm-200' }}"
                    >{{ $label }}</button>
                @endforeach
            </div>

            <div class="flex items-center space-x-2 text-xs">
                <span class="text-slate-500">Sortieren:</span>
                @foreach (['priority' => 'Prio', 'created_at' => 'Datum', 'id' => 'ID'] as $value => $label)
                    <button
                        type="button"
                        wire:click="sortTickets('{{ $value }}')"
                        class="px-3 py-1 rounded-lg font-medium shrink-0 transition {{ $sortField === $value ? 'bg-calm-600 text-white' : 'bg-slatecalm-100 text-slate-600 hover:bg-slatecalm-200' }}"
                    >{{ $label }}@if ($sortField === $value) {{ $sortDirection === 'asc' ? '↑' : '↓' }}@endif</button>
                @endforeach
            </div>
        </div>

        <div class="flex-1 overflow-y-auto divide-y divide-slatecalm-200">
            @forelse ($tickets as $item)
                <div
                    wire:click="selectTicket({{ $item->id }})"
                    class="p-4 cursor-pointer hover:bg-calm-50/60 transition border-l-4 {{ $ticketId === $item->id ? 'bg-calm-50/90 border-calm-600' : 'border-transparent' }}"
                >
                    <div class="flex items-center justify-between mb-1">
                        <span class="text-xs font-mono font-semibold text-calm-700 bg-calm-100 px-2 py-0.5 rounded">#{{ $item->id }}</span>
                        <span class="text-[11px] text-slate-400">{{ $item->created_at->diffForHumans() }}</span>
                    </div>
                    <p class="text-sm font-medium text-slatecalm-900 truncate">{{ $item->subject }}</p>
                    <p class="text-xs text-slate-500 truncate">{{ $item->requester_name ?: $item->requester_email }}</p>
                </div>
            @empty
                <p class="p-4 text-sm text-slate-400">Keine Tickets gefunden.</p>
            @endforelse
        </div>

        <div class="p-3 border-t border-slatecalm-200">
            {{ $tickets->links() }}
        </div>
    </div>

    {{-- Ticket detail --}}
    <div class="flex-1 flex flex-col h-full overflow-hidden bg-slatecalm-50/30">
        @if ($ticket)
            <div
                wire:key="ticket-detail-{{ $ticket->id }}"
                x-data="ticketCollision({{ $ticket->id }}, @js(['id' => auth()->id(), 'name' => auth()->user()->name]))"
                x-init="join()"
                class="flex h-full overflow-hidden"
            >
                <div class="flex flex-col h-full flex-1 min-w-0">
                    <div class="bg-white border-b border-slatecalm-200 px-6 py-4 flex flex-wrap items-center justify-between gap-4 shrink-0">
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center justify-between gap-2">
                                <span class="text-xs font-mono font-bold text-calm-700 bg-calm-100 px-2.5 py-1 rounded-md">#{{ $ticket->id }}</span>
                                <span class="text-xs font-medium text-slate-600">
                                    {{ \App\Models\Ticket::PRIORITY_LABELS[$ticket->priority] ?? $ticket->priority }} | {{ \App\Models\Ticket::STATUS_LABELS[$ticket->status] ?? $ticket->status }}
                                </span>
                            </div>
                            <h2 class="text-lg font-semibold text-slatecalm-900 mt-1">{{ $ticket->subject }}</h2>
                        </div>

                        <div class="flex items-center gap-3">
                            @if ($ticket->source === 'whatsapp')
                                <span class="text-xs px-2.5 py-1 rounded-full font-medium {{ $whatsappSessionOpen ? 'bg-calm-100 text-calm-800' : 'bg-amber-100 text-amber-800' }}">
                                    {{ $whatsappSessionOpen ? '24-Std.-Fenster offen' : '24-Std.-Fenster abgelaufen — nur Vorlagen' }}
                                </span>
                            @endif

                            <div class="flex items-center gap-2 text-xs text-calm-700" x-show="others.length > 0" x-cloak>
                                <span class="w-2 h-2 rounded-full bg-calm-500"></span>
                                <span x-text="collisionLabel()"></span>
                            </div>
                        </div>
                    </div>

                    <div class="flex-1 overflow-y-auto p-6 space-y-4">
                        @foreach ($ticket->messages as $message)
                            <div class="rounded-2xl p-4 border {{ $message->isInternalNote() ? 'bg-ocean-50 border-ocean-100' : 'bg-white border-slatecalm-200' }}">
                                <div class="flex items-center justify-between mb-2 text-xs text-slate-500">
                                    <span class="font-medium text-slatecalm-800">
                                        {{ $message->authorUser?->name ?? $message->authorCustomer?->name ?? $message->external_author_name ?? $message->external_author_email }}
                                    </span>
                                    <span>
                                        @if ($message->isInternalNote())
                                            <span class="text-ocean-700 font-semibold">Interne Notiz</span> ·
                                        @endif
                                        {{ $message->created_at->format('d.m.Y H:i') }}
                                    </span>
                                </div>
                                <div class="text-sm text-slate-700 whitespace-pre-line">{!! $message->body_html ?? nl2br(e($message->body_text)) !!}</div>

                                @if ($message->attachments->isNotEmpty())
                                    <div class="mt-3 flex flex-wrap gap-2">
                                        @foreach ($message->attachments as $attachment)
                                            <span class="text-[11px] px-2 py-1 rounded-md bg-slatecalm-100 text-slate-600">{{ $attachment->original_name }}</span>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>

                    <div class="border-t border-slatecalm-200 bg-white p-4 shrink-0">
                        <div class="flex items-center justify-between gap-2 mb-2 text-xs">
                            <div class="flex items-center gap-2">
                                <button
                                    type="button"
                                    wire:click="$set('replyVisibility', '{{ \App\Models\TicketMessage::VISIBILITY_PUBLIC }}')"
                                    class="px-3 py-1.5 rounded-lg font-medium transition {{ $replyVisibility === 'public' ? 'bg-calm-600 text-white' : 'bg-slatecalm-100 text-slate-600' }}"
                                >Öffentliche Antwort</button>
                                <button
                                    type="button"
                                    wire:click="$set('replyVisibility', '{{ \App\Models\TicketMessage::VISIBILITY_INTERNAL_NOTE }}')"
                                    class="px-3 py-1.5 rounded-lg font-medium transition {{ $replyVisibility === 'internal_note' ? 'bg-ocean-500 text-white' : 'bg-slatecalm-100 text-slate-600' }}"
                                >Interne Notiz</button>
                            </div>

                            @if ($cannedResponses->isNotEmpty())
                                <select
                                    onchange="if(this.value){ @this.call('insertCannedResponse', this.value); this.value=''; }"
                                    class="border border-slatecalm-200 rounded-lg px-2 py-1 text-slate-600"
                                >
                                    <option value="">Textbaustein einfügen...</option>
                                    @foreach ($cannedResponses as $canned)
                                        <option value="{{ $canned->id }}">{{ $canned->title }}</option>
                                    @endforeach
                                </select>
                            @endif
                        </div>

                        @if ($ticket->source === 'whatsapp' && ! $whatsappSessionOpen && $replyVisibility === 'public')
                            <div class="flex gap-2">
                                <select id="whatsappTemplateSelect" class="flex-1 border border-slatecalm-200 rounded-xl px-3 py-2 text-sm">
                                    <option value="">Genehmigte Vorlage wählen...</option>
                                    @foreach ($whatsappTemplates as $template)
                                        <option value="{{ $template->id }}">{{ $template->name }}</option>
                                    @endforeach
                                </select>
                                <button
                                    type="button"
                                    onclick="const v=document.getElementById('whatsappTemplateSelect').value; if(v){ @this.call('sendWhatsappTemplate', v); }"
                                    class="py-2 px-5 bg-calm-600 hover:bg-calm-700 text-white font-medium rounded-xl shadow-sm text-sm"
                                >Vorlage senden</button>
                            </div>
                        @else
                            <textarea
                                wire:model="replyBody"
                                rows="3"
                                placeholder="Antwort verfassen..."
                                class="w-full border border-slatecalm-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-calm-400"
                            ></textarea>
                            @error('replyBody') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror

                            <div class="flex justify-end mt-2">
                                <button
                                    type="button"
                                    wire:click="sendReply"
                                    class="py-2 px-5 bg-calm-600 hover:bg-calm-700 text-white font-medium rounded-xl shadow-sm text-sm"
                                >Senden</button>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Kunden-/Metadaten-Sidebar --}}
                <aside class="w-72 shrink-0 border-l border-slatecalm-200 bg-white p-5 overflow-y-auto hidden lg:block">
                    <h3 class="text-xs font-semibold text-slate-500 uppercase tracking-wide mb-2">Kunde</h3>
                    <p class="text-sm font-medium text-slatecalm-900">{{ $ticket->requester_name ?: '—' }}</p>
                    <p class="text-sm text-slate-500 mb-4">{{ $ticket->requester_email ?: $ticket->requester_phone ?: '—' }}</p>

                    @module('erp-integration')
                        @can('erp.customer.view')
                            <div class="mb-4">
                                <livewire:agent.ticket-erp-panel :ticket-id="$ticket->id" :key="'erp-panel-'.$ticket->id" />
                            </div>
                        @endcan
                    @endmodule

                    <livewire:agent.ticket-properties-panel :ticket-id="$ticket->id" :key="'props-'.$ticket->id" />
                    <livewire:agent.ticket-itil-panel :ticket-id="$ticket->id" :key="'itil-'.$ticket->id" />

                    <h3 class="text-xs font-semibold text-slate-500 uppercase tracking-wide mb-2">Tags</h3>
                    <div class="flex flex-wrap gap-1.5 mb-3">
                        @forelse ($ticket->tags ?? [] as $tag)
                            <span class="inline-flex items-center gap-1 text-[11px] px-2 py-1 rounded-md bg-calm-100 text-calm-800">
                                {{ $tag }}
                                <button type="button" wire:click="removeTag('{{ $tag }}')" class="text-calm-500 hover:text-calm-800">&times;</button>
                            </span>
                        @empty
                            <span class="text-xs text-slate-400">Keine Tags</span>
                        @endforelse
                    </div>
                    <div class="flex gap-1.5">
                        <input
                            type="text"
                            wire:model="newTag"
                            wire:keydown.enter="addTag"
                            placeholder="Tag hinzufügen..."
                            class="flex-1 border border-slatecalm-200 rounded-lg px-2 py-1 text-xs"
                        >
                        <button type="button" wire:click="addTag" class="text-xs px-2.5 py-1 rounded-lg bg-slatecalm-100 text-slate-700">+</button>
                    </div>

                    @module('team-chat')
                        @can('chat.channels.view')
                            <a href="{{ route('agent.chat.ticket', $ticket) }}" class="mt-5 block text-center text-xs px-3 py-2 rounded-lg bg-ocean-50 text-ocean-700 font-medium">Ticket-Chat mit Kollegen</a>
                        @endcan
                    @endmodule

                    @module('field-service')
                        @can('appointments.view.team')
                            @if ($ticket->appointments->isNotEmpty())
                                <h3 class="mt-5 text-xs font-semibold text-slate-500 uppercase tracking-wide mb-2">Vor-Ort-Einsätze</h3>
                                <ul class="space-y-1.5">
                                    @foreach ($ticket->appointments as $appointment)
                                        <li class="text-xs text-slate-600">
                                            {{ $appointment->scheduled_start->format('d.m. H:i') }} · {{ $appointment->state->label() }}
                                            · {{ $appointment->technician?->user->name ?? 'nicht zugewiesen' }}
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                        @endcan
                    @endmodule

                    @module('knowledge-base')
                        @can('kb.articles.view')
                            <div class="mt-5">
                                <livewire:agent.ticket-knowledge-panel :ticket-id="$ticket->id" :key="'kb-panel-'.$ticket->id" />
                            </div>
                        @endcan
                    @endmodule
                </aside>
            </div>
        @else
            <div class="flex-1 flex items-center justify-center text-slate-400 text-sm">
                Ticket auswählen, um die Konversation anzuzeigen.
            </div>
        @endif
    </div>
</div>
