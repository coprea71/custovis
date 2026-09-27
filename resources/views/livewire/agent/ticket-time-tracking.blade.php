<div class="mb-4">
    <h3 class="text-xs font-semibold text-slate-500 uppercase tracking-wide mb-2">Zeiterfassung</h3>
    <p class="text-sm text-slatecalm-900 mb-2">
        {{ \App\Support\Duration::format($billableMinutes) }} h abrechenbar
        @if ($otherMinutes)
            <span class="text-slate-400">· {{ \App\Support\Duration::format($otherMinutes) }} h intern</span>
        @endif
    </p>

    @if ($running)
        <div class="flex items-center justify-between gap-2 mb-3 rounded-xl bg-calm-50 px-3 py-2 text-xs text-calm-800" wire:poll.60s>
            <span>Timer läuft seit {{ $running->started_at->format('H:i') }} Uhr</span>
            <button type="button" wire:click="stopTimer" class="px-3 py-1.5 rounded-lg bg-calm-600 text-white font-medium">Stopp</button>
        </div>
    @endif

    <form wire:submit="save" class="space-y-2 text-sm">
        <div class="flex gap-2">
            <label class="flex-1 text-xs text-slate-600">Dauer
                <input type="text" wire:model="duration" placeholder="1:30" maxlength="7" inputmode="numeric" class="w-full mt-1 border border-slatecalm-200 rounded-xl px-3 py-2 text-sm">
            </label>
            <label class="flex-1 text-xs text-slate-600">Datum
                <input type="date" wire:model="workDate" max="{{ today()->toDateString() }}" class="w-full mt-1 border border-slatecalm-200 rounded-xl px-2 py-2 text-sm">
            </label>
        </div>
        @error('duration') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
        @error('workDate') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
        <input type="text" wire:model="description" placeholder="Tätigkeit (optional)" maxlength="500" aria-label="Tätigkeit" class="w-full border border-slatecalm-200 rounded-xl px-3 py-2 text-sm">
        <label class="flex items-center gap-2 text-xs text-slate-600">
            <input type="checkbox" wire:model="billable" class="rounded border-slatecalm-300"> abrechenbar
        </label>
        <div class="flex gap-2">
            <button type="submit" class="flex-1 px-3 py-2 bg-calm-600 hover:bg-calm-700 text-white rounded-xl text-xs font-medium">Zeit buchen</button>
            @unless ($running)
                <button type="button" wire:click="startTimer" class="px-3 py-2 bg-slatecalm-100 text-slate-700 rounded-xl text-xs font-medium">Timer starten</button>
            @endunless
        </div>
    </form>

    @if ($entries->isNotEmpty())
        <ul class="mt-3 divide-y divide-slatecalm-100 text-xs">
            @foreach ($entries as $entry)
                <li wire:key="time-{{ $entry->id }}" class="py-2 flex justify-between gap-2">
                    <div class="min-w-0">
                        <p class="text-slatecalm-900">
                            {{ $entry->isRunning() ? 'läuft' : \App\Support\Duration::format($entry->minutes).' h' }}
                            · {{ $entry->work_date->format('d.m.Y') }} · {{ $entry->user?->name ?? '—' }}
                        </p>
                        @if ($entry->description)
                            <p class="text-slate-500 truncate">{{ $entry->description }}</p>
                        @endif
                        <p class="text-slate-400">{{ $entry->isLocked() ? 'abgerechnet' : ($entry->billable ? 'abrechenbar' : 'intern') }}</p>
                    </div>
                    @if (! $entry->isLocked() && $entry->user_id === auth()->id())
                        <button type="button" wire:click="delete({{ $entry->id }})" wire:confirm="Zeiteintrag löschen?" class="shrink-0 text-red-700" aria-label="Zeiteintrag löschen">&times;</button>
                    @endif
                </li>
            @endforeach
        </ul>
    @endif
</div>
