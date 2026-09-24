<div class="flex-1 overflow-y-auto p-6">
    <form wire:submit="save" class="max-w-3xl bg-white border border-slatecalm-200 rounded-2xl p-6 space-y-4 text-sm">
        <div class="flex items-center justify-between">
            <h1 class="text-xl font-semibold text-slatecalm-900">Neues Ticket</h1>
            <a href="{{ route('agent.tickets.index') }}" class="text-slate-500 hover:text-calm-700">Abbrechen</a>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <label>Team
                <select wire:model="form.team_id" class="w-full mt-1 border border-slatecalm-200 rounded-xl px-3 py-2">
                    @foreach ($teams as $team)<option value="{{ $team->id }}">{{ $team->name }}</option>@endforeach
                </select>
            </label>
            <label>Typ
                <select wire:model="form.type" class="w-full mt-1 border border-slatecalm-200 rounded-xl px-3 py-2">
                    @foreach (['support_ticket' => 'Support-Ticket', 'incident' => 'Incident', 'problem' => 'Problem', 'change' => 'Change'] as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            <label>Priorität
                <select wire:model="form.priority" class="w-full mt-1 border border-slatecalm-200 rounded-xl px-3 py-2">
                    @foreach (['low' => 'Niedrig', 'normal' => 'Normal', 'high' => 'Hoch', 'urgent' => 'Dringend'] as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            <label class="md:col-span-3">Betreff<input type="text" wire:model="form.subject" class="w-full mt-1 border border-slatecalm-200 rounded-xl px-3 py-2"></label>
            <label>Anfragende/r<input type="text" wire:model="form.requester_name" class="w-full mt-1 border border-slatecalm-200 rounded-xl px-3 py-2"></label>
            <label>E-Mail<input type="email" wire:model="form.requester_email" class="w-full mt-1 border border-slatecalm-200 rounded-xl px-3 py-2"></label>
            <label>Telefon<input type="tel" wire:model="form.requester_phone" class="w-full mt-1 border border-slatecalm-200 rounded-xl px-3 py-2"></label>
            <label class="md:col-span-3">Beschreibung<textarea wire:model="form.body" rows="6" class="w-full mt-1 border border-slatecalm-200 rounded-xl px-3 py-2"></textarea></label>
        </div>

        <p class="text-xs text-red-600">{{ $errors->first('form.*') }}</p>
        <div class="flex justify-end">
            <button type="submit" class="px-5 py-2 bg-calm-600 hover:bg-calm-700 text-white rounded-xl font-medium">Ticket anlegen</button>
        </div>
    </form>
</div>
