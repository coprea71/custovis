<div class="space-y-3 text-xs mb-5">
    @if ($extension)
        <h3 class="font-semibold text-slate-500 uppercase tracking-wide">ITIL · {{ ['incident' => 'Incident', 'problem' => 'Problem', 'change' => 'Change', 'service_request' => 'Service-Request'][$ticket->type] }}</h3>
        <p class="text-sm">Zustand: <strong class="text-slatecalm-900">{{ $extension->state->label() }}</strong></p>

        @if ($transitions->isNotEmpty())
            <div class="flex flex-wrap gap-1.5">
                @foreach ($transitions as $class => $label)
                    <button type="button" wire:click="changeState(@js($class))" class="px-2.5 py-1 rounded-lg bg-calm-100 text-calm-800 font-medium">→ {{ $label }}</button>
                @endforeach
            </div>
        @endif

        <form wire:submit="saveDetails" class="space-y-1.5">
            @if ($ticket->type === 'incident')
                @foreach (['impact' => 'Auswirkung', 'urgency' => 'Dringlichkeit'] as $field => $label)
                    <label class="block text-slate-600">{{ $label }}
                        <select wire:model="details.{{ $field }}" class="w-full mt-0.5 border border-slatecalm-200 rounded-lg px-2 py-1">
                            @foreach (['low' => 'niedrig', 'medium' => 'mittel', 'high' => 'hoch'] as $value => $text)<option value="{{ $value }}">{{ $text }}</option>@endforeach
                        </select>
                    </label>
                @endforeach
            @elseif ($ticket->type === 'problem')
                <label class="block text-slate-600">Ursache (Root Cause)
                    <textarea wire:model="details.root_cause" rows="3" class="w-full mt-0.5 border border-slatecalm-200 rounded-lg px-2 py-1"></textarea>
                </label>
            @elseif ($ticket->type === 'change')
                <label class="block text-slate-600">Change-Typ
                    <select wire:model="details.change_type" class="w-full mt-0.5 border border-slatecalm-200 rounded-lg px-2 py-1">
                        @foreach (['standard' => 'Standard', 'normal' => 'Normal', 'emergency' => 'Notfall'] as $value => $text)<option value="{{ $value }}">{{ $text }}</option>@endforeach
                    </select>
                </label>
                <label class="block text-slate-600">Risiko
                    <select wire:model="details.risk_level" class="w-full mt-0.5 border border-slatecalm-200 rounded-lg px-2 py-1">
                        @foreach (['low' => 'niedrig', 'medium' => 'mittel', 'high' => 'hoch'] as $value => $text)<option value="{{ $value }}">{{ $text }}</option>@endforeach
                    </select>
                </label>
                <label class="block text-slate-600">Geplanter Beginn<input type="datetime-local" wire:model="details.planned_start" class="w-full mt-0.5 border border-slatecalm-200 rounded-lg px-2 py-1"></label>
                <label class="block text-slate-600">Geplantes Ende<input type="datetime-local" wire:model="details.planned_end" class="w-full mt-0.5 border border-slatecalm-200 rounded-lg px-2 py-1"></label>
            @endif
            @if ($ticket->type !== 'service_request')
                <button type="submit" class="w-full py-1 rounded-lg bg-slatecalm-100 text-slate-700">Details speichern</button>
            @endif
        </form>

        @if ($ticket->type === 'change')
            @if ($ticket->change->approvals->isNotEmpty())
                <ul class="space-y-0.5">
                    @foreach ($ticket->change->approvals as $approval)
                        <li>{{ $approval->approver->name }}: {{ ['pending' => 'offen', 'approved' => 'genehmigt', 'rejected' => 'abgelehnt'][$approval->decision] }}</li>
                    @endforeach
                </ul>
            @endif
            @if ($extension->state->equals(\App\States\Change\Draft::class))
                <div class="space-y-1">
                    <p class="text-slate-600">CAB-Freigabe anfordern bei:</p>
                    <select wire:model="approverIds" multiple size="3" aria-label="Genehmigende Personen" class="w-full border border-slatecalm-200 rounded-lg px-2 py-1">
                        @foreach ($candidates as $candidate)<option value="{{ $candidate->id }}">{{ $candidate->name }}</option>@endforeach
                    </select>
                    <button type="button" wire:click="requestCab" class="w-full py-1 rounded-lg bg-ocean-50 text-ocean-700 font-medium">Freigabe anfordern</button>
                </div>
            @endif
        @endif
    @endif

    <div>
        <h3 class="font-semibold text-slate-500 uppercase tracking-wide mb-1">Configuration Items</h3>
        @foreach ($ticket->configurationItems as $ci)
            <p class="flex justify-between">{{ $ci->name }} <span class="text-slate-400">{{ $ci->type }}</span>
                <button type="button" wire:click="detachCi({{ $ci->id }})" class="text-slate-400 hover:text-red-600" aria-label="CI entfernen">&times;</button></p>
        @endforeach
        @if ($cis->isNotEmpty())
            <div class="flex gap-1 mt-1">
                <select wire:model="ciId" aria-label="CI zuordnen" class="flex-1 border border-slatecalm-200 rounded-lg px-2 py-1">
                    <option value="">CI zuordnen...</option>
                    @foreach ($cis as $ci)<option value="{{ $ci->id }}">{{ $ci->name }}</option>@endforeach
                </select>
                <button type="button" wire:click="attachCi" class="px-2 rounded-lg bg-slatecalm-100">+</button>
            </div>
        @endif
    </div>
    @if ($errors->any()) <p class="text-red-600">{{ $errors->first() }}</p> @endif
</div>
