<div class="space-y-6">
    <div>
        <h1 class="text-xl font-semibold text-slatecalm-900">Spam</h1>
        <p class="text-sm text-slate-500">Tickets, die Agenten als Spam markiert haben, und neue Mails von gesperrten Absendern landen hier statt in der Ticketliste. Sie werden nach {{ $retentionDays }} Tagen automatisch gelöscht. „Freigeben“ stellt ein Ticket wieder in die Ticketliste.</p>
    </div>

    <div class="bg-white border border-slatecalm-200 rounded-2xl divide-y divide-slatecalm-200">
        @forelse ($tickets as $ticket)
            <div wire:key="spam-{{ $ticket->id }}" class="p-4 space-y-3">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div class="min-w-0">
                        <p class="font-medium text-slatecalm-900 break-words">#{{ $ticket->id }} · {{ $ticket->subject }}</p>
                        <p class="text-xs text-slate-500 break-all">{{ $ticket->requester_name ? $ticket->requester_name.' · ' : '' }}{{ $ticket->requester_email }} · Team {{ $ticket->team?->name }}</p>
                        <p class="text-xs text-slate-400">Spam seit {{ $ticket->spam_at->format('d.m.Y H:i') }} · wird gelöscht am {{ $ticket->spam_at->copy()->addDays($retentionDays)->format('d.m.Y') }}</p>
                    </div>
                    <div class="flex gap-2 text-xs">
                        <button type="button" wire:click="togglePreview({{ $ticket->id }})" class="px-3 py-1.5 rounded-lg bg-slatecalm-100 text-slate-700">{{ $previewId === $ticket->id ? 'Vorschau schließen' : 'Vorschau' }}</button>
                        <button type="button" wire:click="release({{ $ticket->id }})" class="px-3 py-1.5 rounded-lg bg-calm-100 text-calm-800 font-medium">Freigeben</button>
                        <button type="button" wire:click="delete({{ $ticket->id }})" wire:confirm="Ticket #{{ $ticket->id }} endgültig löschen?" class="px-3 py-1.5 rounded-lg bg-red-50 text-red-700 font-medium">Löschen</button>
                    </div>
                </div>

                @if ($previewId === $ticket->id)
                    {{-- Plain text only: spam HTML is never rendered in the admin. --}}
                    <div class="space-y-2">
                        @foreach ($ticket->messages as $message)
                            <pre class="whitespace-pre-wrap break-words text-sm text-slate-700 bg-slatecalm-50 border border-slatecalm-200 rounded-xl p-3 max-h-64 overflow-y-auto">{{ $message->body_text ?: strip_tags((string) $message->body_html) }}</pre>
                        @endforeach
                    </div>
                @endif
            </div>
        @empty
            <p class="p-4 text-sm text-slate-500">Der Spam-Ordner ist leer.</p>
        @endforelse
    </div>

    {{ $tickets->links() }}

    <div>
        <h2 class="text-lg font-semibold text-slatecalm-900">Gesperrte Absender</h2>
        <p class="text-sm text-slate-500">Neue Mails dieser Absender werden für das jeweilige Team als Spam archiviert. Eine Domain-Sperre gilt auch für ihre Subdomains.</p>
    </div>

    <div class="bg-white border border-slatecalm-200 rounded-2xl divide-y divide-slatecalm-200">
        @forelse ($rules as $rule)
            <div wire:key="rule-{{ $rule->id }}" class="p-4 flex flex-wrap items-center justify-between gap-3">
                <div class="min-w-0">
                    <p class="font-medium text-slatecalm-900 break-all">{{ $rule->type === \App\Models\SpamRule::TYPE_DOMAIN ? '@'.$rule->value : $rule->value }}</p>
                    <p class="text-xs text-slate-500">{{ $rule->type === \App\Models\SpamRule::TYPE_DOMAIN ? 'Domain' : 'Adresse' }} · Team {{ $rule->team?->name }} · {{ $rule->creator?->name ?? 'unbekannt' }}, {{ $rule->created_at->format('d.m.Y') }}</p>
                </div>
                <button type="button" wire:click="deleteRule({{ $rule->id }})" wire:confirm="Sperre aufheben?" class="px-3 py-1.5 rounded-lg bg-slatecalm-100 text-slate-700 text-xs">Sperre aufheben</button>
            </div>
        @empty
            <p class="p-4 text-sm text-slate-500">Keine gesperrten Absender.</p>
        @endforelse
    </div>
</div>
