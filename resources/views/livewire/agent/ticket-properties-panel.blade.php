<div class="space-y-2 text-xs mb-5">
    <h3 class="font-semibold text-slate-500 uppercase tracking-wide">Bearbeitung</h3>
    <label class="block text-slate-600">Status
        <select wire:model.live="status" class="w-full mt-0.5 border border-slatecalm-200 rounded-lg px-2 py-1.5">
            @foreach (\App\Models\Ticket::STATUS_LABELS as $value => $label)
                <option value="{{ $value }}">{{ $label }}</option>
            @endforeach
        </select>
    </label>
    <label class="block text-slate-600">Priorität
        <select wire:model.live="priority" class="w-full mt-0.5 border border-slatecalm-200 rounded-lg px-2 py-1.5">
            @foreach (\App\Models\Ticket::PRIORITY_LABELS as $value => $label)
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
        <select wire:model.live="assigned_to" class="w-full mt-0.5 border border-slatecalm-200 rounded-lg px-2 py-1.5">
            <option value="">— nicht zugewiesen —</option>
            @foreach ($agents as $agent)<option value="{{ $agent->id }}">{{ $agent->name }}</option>@endforeach
        </select>
    </label>
    @if ($errors->any()) <p class="text-red-600">{{ $errors->first() }}</p> @endif
</div>
