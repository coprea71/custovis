<div class="space-y-6">
    <div class="flex items-center justify-between">
        <h1 class="text-xl font-semibold text-slatecalm-900">Techniker</h1>
        <a href="{{ route('admin.checklists') }}" class="px-4 py-2 bg-slatecalm-100 text-slate-700 rounded-xl text-sm font-medium">Checklisten-Vorlagen</a>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <section class="bg-white border border-slatecalm-200 rounded-2xl p-4 space-y-3">
            <ul class="space-y-1">
                @forelse ($technicians as $technician)
                    <li wire:key="t-{{ $technician->id }}">
                        <button type="button" wire:click="select({{ $technician->id }})"
                                class="w-full text-left px-3 py-2 rounded-lg text-sm {{ $selectedId === $technician->id ? 'bg-calm-100 text-calm-800' : 'hover:bg-slatecalm-100' }}">
                            {{ $technician->user->name }} @unless ($technician->active) <span class="text-xs text-slate-400">(inaktiv)</span> @endunless
                        </button>
                    </li>
                @empty
                    <li class="text-sm text-slate-400">Noch keine Techniker.</li>
                @endforelse
            </ul>
            <div class="flex gap-2 pt-3 border-t border-slatecalm-200">
                <select wire:model="newUserId" class="flex-1 border border-slatecalm-200 rounded-xl px-2 py-1.5 text-sm" aria-label="Nutzer als Techniker anlegen">
                    <option value="">Nutzer wählen...</option>
                    @foreach ($candidates as $candidate)
                        <option value="{{ $candidate->id }}">{{ $candidate->name }}</option>
                    @endforeach
                </select>
                <button type="button" wire:click="createProfile" class="px-3 py-1.5 bg-calm-600 text-white rounded-xl text-sm">+</button>
            </div>
            @error('newUserId') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
            <div class="flex gap-2 pt-3 border-t border-slatecalm-200">
                <input type="text" wire:model="newSkill" placeholder="Neuer Skill" class="flex-1 border border-slatecalm-200 rounded-xl px-2 py-1.5 text-sm">
                <button type="button" wire:click="createSkill" class="px-3 py-1.5 bg-slatecalm-100 rounded-xl text-sm">Anlegen</button>
            </div>
            @error('newSkill') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
        </section>

        @if ($selected)
            <section class="md:col-span-2 space-y-4">
                <div class="bg-white border border-slatecalm-200 rounded-2xl p-4 space-y-3">
                    <h2 class="font-semibold text-slatecalm-900">{{ $selected->user->name }}</h2>
                    <label class="block text-sm">Heimatstandort (Adresse)
                        <input type="text" wire:model="profile.home_address" class="w-full mt-1 border border-slatecalm-200 rounded-xl px-3 py-2">
                    </label>
                    <p class="text-xs text-slate-400">Koordinaten: {{ $selected->home_lat !== null ? $selected->home_lat.', '.$selected->home_lng : 'nicht ermittelt (Nominatim nicht konfiguriert oder Adresse unbekannt)' }}</p>
                    <label class="flex items-center gap-2 text-sm"><input type="checkbox" wire:model="profile.active"> aktiv</label>
                    <label class="flex items-center gap-2 text-sm"><input type="checkbox" wire:model="profile.location_tracking_consent"> Einwilligung zur Standortübermittlung liegt vor</label>
                    <button type="button" wire:click="saveProfile" class="px-4 py-2 bg-calm-600 text-white rounded-xl text-sm">Speichern</button>
                </div>

                <div class="bg-white border border-slatecalm-200 rounded-2xl p-4 space-y-2">
                    <h3 class="text-sm font-semibold text-slatecalm-900">Skills</h3>
                    @foreach ($selected->skills as $skill)
                        <p class="text-sm flex justify-between">{{ $skill->name }} (Level {{ $skill->pivot->level }})
                            <button type="button" wire:click="removeSkill({{ $skill->id }})" class="text-xs text-red-600">Entfernen</button></p>
                    @endforeach
                    <div class="flex gap-2">
                        <select wire:model="skillForm.skill_id" class="flex-1 border border-slatecalm-200 rounded-xl px-2 py-1.5 text-sm" aria-label="Skill">
                            <option value="">Skill wählen...</option>
                            @foreach ($skills as $skill)<option value="{{ $skill->id }}">{{ $skill->name }}</option>@endforeach
                        </select>
                        <input type="number" min="1" max="5" wire:model="skillForm.level" class="w-16 border border-slatecalm-200 rounded-xl px-2 py-1.5 text-sm" aria-label="Level">
                        <button type="button" wire:click="addSkill" class="px-3 py-1.5 bg-slatecalm-100 rounded-xl text-sm">+</button>
                    </div>
                </div>

                <div class="bg-white border border-slatecalm-200 rounded-2xl p-4 space-y-2">
                    <h3 class="text-sm font-semibold text-slatecalm-900">Schichten</h3>
                    @foreach ($selected->shifts->sortBy('weekday') as $shift)
                        <p class="text-sm flex justify-between">{{ \App\Models\TechnicianShift::WEEKDAYS[$shift->weekday] }} {{ substr($shift->starts_at, 0, 5) }}–{{ substr($shift->ends_at, 0, 5) }}
                            <button type="button" wire:click="removeShift({{ $shift->id }})" class="text-xs text-red-600">Entfernen</button></p>
                    @endforeach
                    <div class="flex gap-2">
                        <select wire:model="shiftForm.weekday" class="border border-slatecalm-200 rounded-xl px-2 py-1.5 text-sm" aria-label="Wochentag">
                            @foreach (\App\Models\TechnicianShift::WEEKDAYS as $number => $label)<option value="{{ $number }}">{{ $label }}</option>@endforeach
                        </select>
                        <input type="time" wire:model="shiftForm.starts_at" class="border border-slatecalm-200 rounded-xl px-2 py-1.5 text-sm" aria-label="Beginn">
                        <input type="time" wire:model="shiftForm.ends_at" class="border border-slatecalm-200 rounded-xl px-2 py-1.5 text-sm" aria-label="Ende">
                        <button type="button" wire:click="addShift" class="px-3 py-1.5 bg-slatecalm-100 rounded-xl text-sm">+</button>
                    </div>
                    @error('shiftForm.ends_at') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="bg-white border border-slatecalm-200 rounded-2xl p-4 space-y-2">
                    <h3 class="text-sm font-semibold text-slatecalm-900">Abwesenheiten</h3>
                    @foreach ($selected->absences->sortBy('starts_on') as $absence)
                        <p class="text-sm flex justify-between">{{ $absence->starts_on->format('d.m.Y') }}–{{ $absence->ends_on->format('d.m.Y') }} · {{ \App\Models\TechnicianAbsence::REASONS[$absence->reason] ?? $absence->reason }}
                            <button type="button" wire:click="removeAbsence({{ $absence->id }})" class="text-xs text-red-600">Entfernen</button></p>
                    @endforeach
                    <div class="flex flex-wrap gap-2">
                        <input type="date" wire:model="absenceForm.starts_on" class="border border-slatecalm-200 rounded-xl px-2 py-1.5 text-sm" aria-label="Von">
                        <input type="date" wire:model="absenceForm.ends_on" class="border border-slatecalm-200 rounded-xl px-2 py-1.5 text-sm" aria-label="Bis">
                        <select wire:model="absenceForm.reason" class="border border-slatecalm-200 rounded-xl px-2 py-1.5 text-sm" aria-label="Grund">
                            @foreach (\App\Models\TechnicianAbsence::REASONS as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach
                        </select>
                        <button type="button" wire:click="addAbsence" class="px-3 py-1.5 bg-slatecalm-100 rounded-xl text-sm">+</button>
                    </div>
                    @error('absenceForm.ends_on') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
            </section>
        @endif
    </div>
</div>
