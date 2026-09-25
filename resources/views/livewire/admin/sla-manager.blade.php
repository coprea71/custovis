<div class="space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <h1 class="text-xl font-semibold text-slatecalm-900">SLA &amp; Geschäftszeiten</h1>
        <select wire:change="selectTeam($event.target.value)" aria-label="Team" class="border border-slatecalm-200 rounded-xl px-3 py-2 text-sm">
            @foreach ($teams as $team)<option value="{{ $team->id }}" @selected($teamId === $team->id)>{{ $team->name }}</option>@endforeach
        </select>
    </div>

    @if (session('status')) <p class="text-sm rounded-xl px-4 py-3 bg-calm-50 text-calm-800">{{ session('status') }}</p> @endif

    @if ($teamId)
        <form wire:submit="save" class="space-y-6">
            <section class="bg-white border border-slatecalm-200 rounded-2xl p-6">
                <h2 class="font-semibold text-slatecalm-900 mb-1">SLA-Ziele je Priorität</h2>
                <p class="text-xs text-slate-500 mb-3">In Minuten; leer = keine SLA für diese Priorität.</p>
                <table class="w-full text-sm">
                    <thead><tr class="text-left text-slate-500"><th class="py-1">Priorität</th><th>Erste Reaktion</th><th>Lösung</th></tr></thead>
                    <tbody>
                        @foreach (\App\Models\Ticket::PRIORITY_LABELS as $priority => $label)
                            <tr>
                                <td class="py-1.5">{{ $label }}</td>
                                <td><input type="number" min="1" wire:model="policies.{{ $priority }}.response" aria-label="Reaktion {{ $label }}" class="w-28 border border-slatecalm-200 rounded-lg px-2 py-1"></td>
                                <td><input type="number" min="1" wire:model="policies.{{ $priority }}.resolution" aria-label="Lösung {{ $label }}" class="w-28 border border-slatecalm-200 rounded-lg px-2 py-1"></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </section>

            <section class="bg-white border border-slatecalm-200 rounded-2xl p-6">
                <h2 class="font-semibold text-slatecalm-900 mb-1">Geschäftszeiten</h2>
                <p class="text-xs text-slate-500 mb-3">SLA-Fristen laufen nur innerhalb dieser Zeiten. Ohne Einträge zählt die Kalenderzeit (24/7).</p>
                <div class="space-y-1.5 text-sm">
                    @foreach (\App\Livewire\Admin\SlaManager::DAYS as $day => $label)
                        <div class="flex items-center gap-2">
                            <span class="w-8">{{ $label }}</span>
                            <input type="time" wire:model="hours.{{ $day }}.start" aria-label="{{ $label }} Beginn" class="border border-slatecalm-200 rounded-lg px-2 py-1">
                            <span>–</span>
                            <input type="time" wire:model="hours.{{ $day }}.end" aria-label="{{ $label }} Ende" class="border border-slatecalm-200 rounded-lg px-2 py-1">
                        </div>
                    @endforeach
                </div>
            </section>

            <p class="text-xs text-red-600">{{ $errors->first() }}</p>
            <div class="flex justify-end">
                <button type="submit" class="px-5 py-2 bg-calm-600 hover:bg-calm-700 text-white rounded-xl text-sm font-medium">Speichern</button>
            </div>
        </form>
    @else
        <p class="text-sm text-slate-400">Bitte zuerst ein Team anlegen.</p>
    @endif
</div>
