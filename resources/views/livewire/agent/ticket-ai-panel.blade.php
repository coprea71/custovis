<div>
    <h3 class="text-xs font-semibold text-slate-500 uppercase tracking-wide mb-2">KI-Assistent</h3>

    @if ($summarizeAvailable || $suggestReplyAvailable)
        <div class="flex flex-wrap gap-1.5">
            @if ($summarizeAvailable)
                <button type="button" wire:click="request('summarize')" class="text-xs px-2.5 py-1 rounded-lg bg-slatecalm-100 text-slate-700 hover:bg-slatecalm-200">Zusammenfassen</button>
            @endif
            @if ($suggestReplyAvailable)
                <button type="button" wire:click="request('suggest_reply')" class="text-xs px-2.5 py-1 rounded-lg bg-slatecalm-100 text-slate-700 hover:bg-slatecalm-200">Antwort vorschlagen</button>
            @endif
        </div>
    @else
        <p class="text-xs text-slate-400">Kein KI-Provider eingerichtet oder Monatsbudget aufgebraucht.</p>
    @endif

    @if ($status)
        <p class="mt-2 text-xs text-slate-500">{{ $status }}</p>
    @endif
</div>
