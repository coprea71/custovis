<div class="border-t border-slatecalm-200 pt-4 flex items-center gap-3 text-sm">
    @if ($voted)
        <span class="text-calm-700">Danke für Ihr Feedback!</span>
    @else
        <span class="text-slate-600">War dieser Artikel hilfreich?</span>
        <button type="button" wire:click="vote(true)" class="px-3 py-1.5 rounded-lg bg-calm-100 text-calm-800 font-medium">Ja</button>
        <button type="button" wire:click="vote(false)" class="px-3 py-1.5 rounded-lg bg-slatecalm-100 text-slate-600 font-medium">Nein</button>
    @endif
</div>
