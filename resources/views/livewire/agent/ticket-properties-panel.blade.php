<form wire:submit="save" class="space-y-2 text-xs mb-5">
    <h3 class="font-semibold text-slate-500 uppercase tracking-wide">Bearbeitung</h3>
    <label class="block text-slate-600">Status
        <select wire:model="status" class="w-full mt-0.5 border border-slatecalm-200 rounded-lg px-2 py-1.5">
            @foreach (['open' => 'Offen', 'pending' => 'Wartend', 'reopened' => 'Wieder geöffnet', 'closed' => 'Geschlossen'] as $value => $label)
                <option value="{{ $value }}">{{ $label }}</option>
            @endforeach
        </select>
    </label>
    <label class="block text-slate-600">Priorität
        <select wire:model="priority" class="w-full mt-0.5 border border-slatecalm-200 rounded-lg px-2 py-1.5">
            @foreach (['low' => 'Niedrig', 'normal' => 'Normal', 'high' => 'Hoch', 'urgent' => 'Dringend'] as $value => $label)
                <option value="{{ $value }}">{{ $label }}</option>
            @endforeach
        </select>
    </label>
    <label class="block text-slate-600">Team
        <select wire:model.live="team_id" class="w-full mt-0.5 border border-slatecalm-200 rounded-lg px-2 py-1.5">
            @foreach ($teams as $team)<option value="{{ $team->id }}">{{ $team->name }}</option>@endforeach
        </select>
    </label>
    <label class="block text-slate-600">Bearbeiter
        <select wire:model="assigned_to" class="w-full mt-0.5 border border-slatecalm-200 rounded-lg px-2 py-1.5">
            <option value="">— nicht zugewiesen —</option>
            @foreach ($agents as $agent)<option value="{{ $agent->id }}">{{ $agent->name }}</option>@endforeach
        </select>
    </label>
    @if ($errors->any()) <p class="text-red-600">{{ $errors->first() }}</p> @endif
    <button type="submit" class="w-full py-1.5 rounded-lg bg-calm-600 text-white font-medium">Übernehmen</button>
</form>
