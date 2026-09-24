<div class="flex-1 overflow-y-auto p-6 space-y-4">
    @vite(['resources/js/dispatch.js'])

    <div class="flex flex-wrap items-center justify-between gap-3">
        <h1 class="text-xl font-semibold text-slatecalm-900">Einsatzplanung</h1>
        <input type="date" wire:model.live="date" class="border border-slatecalm-200 rounded-xl px-3 py-2 text-sm" aria-label="Tag">
    </div>

    @error('board') <p class="text-sm text-red-600">{{ $message }}</p> @enderror

    <div wire:key="map-{{ md5(json_encode($mapPoints)) }}" x-data="dispatchMap(@js($mapPoints), @js(config('custovis.field_service.tile_url')))"
         class="h-64 rounded-2xl border border-slatecalm-200 z-0"></div>

    <div class="grid grid-cols-1 lg:grid-cols-4 gap-4">
        {{-- Unassigned appointments (drag source) --}}
        <section class="bg-white border border-slatecalm-200 rounded-2xl p-4 space-y-2">
            <h2 class="text-sm font-semibold text-slatecalm-900">Nicht zugewiesen</h2>
            @forelse ($unassigned as $appointment)
                @include('livewire.agent.partials.dispatch-card', ['appointment' => $appointment])
            @empty
                <p class="text-xs text-slate-400">Keine offenen Einsätze.</p>
            @endforelse
        </section>

        {{-- Technician columns (drop targets) --}}
        <section class="lg:col-span-3 grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
            @forelse ($technicians as $technician)
                <div wire:key="tech-{{ $technician->id }}" x-data="{ over: false }"
                     @dragover.prevent="over = true" @dragleave="over = false"
                     @drop.prevent="over = false; $wire.assign(Number($event.dataTransfer.getData('text/plain')), {{ $technician->id }})"
                     :class="over ? 'ring-2 ring-calm-400' : ''"
                     class="bg-white border border-slatecalm-200 rounded-2xl p-4 space-y-2 min-h-32">
                    <h2 class="text-sm font-semibold text-slatecalm-900">{{ $technician->user->name }}</h2>
                    @foreach ($byTechnician->get($technician->id, collect()) as $appointment)
                        @include('livewire.agent.partials.dispatch-card', ['appointment' => $appointment])
                    @endforeach
                </div>
            @empty
                <p class="text-sm text-slate-400">Noch keine aktiven Techniker angelegt (Administration → Techniker).</p>
            @endforelse
        </section>
    </div>

    @if ($suggestions->isNotEmpty())
        <section class="bg-white border border-slatecalm-200 rounded-2xl p-4">
            <div class="flex items-center justify-between mb-2">
                <h2 class="text-sm font-semibold text-slatecalm-900">Vorschläge für Einsatz #{{ $suggestFor }}</h2>
                <button type="button" wire:click="$set('suggestFor', null)" class="text-xs text-slate-400">Schließen</button>
            </div>
            @if ($suggestions->first()['sla_risk'])
                <p class="text-xs text-red-600 mb-2">Achtung: Die SLA-Lösungsfrist des Tickets endet vor dem Termin.</p>
            @endif
            <ul class="divide-y divide-slatecalm-100">
                @foreach ($suggestions as $suggestion)
                    <li class="py-2 flex items-center justify-between gap-3 text-sm">
                        <span>
                            <span class="font-medium text-slatecalm-900">{{ $suggestion['technician']->user->name }}</span>
                            <span class="text-xs text-slate-500">
                                {{ $suggestion['blocker'] ? '— '.$suggestion['blocker'] : '· Score '.$suggestion['score'] }}
                                @if ($suggestion['distance_km'] !== null) · {{ number_format($suggestion['distance_km'], 1, ',', '.') }} km @endif
                            </span>
                        </span>
                        @unless ($suggestion['blocker'])
                            <button type="button" wire:click="assign({{ $suggestFor }}, {{ $suggestion['technician']->id }})" class="text-xs px-3 py-1.5 rounded-lg bg-calm-600 text-white">Zuweisen</button>
                        @endunless
                    </li>
                @endforeach
            </ul>
        </section>
    @endif

    <section class="bg-white border border-slatecalm-200 rounded-2xl p-4">
        <h2 class="text-sm font-semibold text-slatecalm-900 mb-3">Neuer Einsatz am {{ \Illuminate\Support\Carbon::parse($date)->format('d.m.Y') }}</h2>
        <form wire:submit="createAppointment" class="grid grid-cols-1 md:grid-cols-3 gap-3 text-sm">
            <label>Ticket-Nr.<input type="number" wire:model="form.ticket_id" class="w-full mt-1 border border-slatecalm-200 rounded-xl px-3 py-2"></label>
            <label>Art
                <select wire:model="form.kind" class="w-full mt-1 border border-slatecalm-200 rounded-xl px-3 py-2">
                    <option value="service">Vor-Ort-Service</option>
                    <option value="delivery">Ware ausliefern</option>
                </select>
            </label>
            <label>Checkliste
                <select wire:model="form.checklist_template_id" class="w-full mt-1 border border-slatecalm-200 rounded-xl px-3 py-2">
                    <option value="">— keine —</option>
                    @foreach ($templates as $template)
                        <option value="{{ $template->id }}">{{ $template->name }}</option>
                    @endforeach
                </select>
            </label>
            <label class="md:col-span-2">Adresse<input type="text" wire:model="form.address" class="w-full mt-1 border border-slatecalm-200 rounded-xl px-3 py-2"></label>
            <div class="grid grid-cols-2 gap-2">
                <label>Beginn<input type="time" wire:model="form.start_time" class="w-full mt-1 border border-slatecalm-200 rounded-xl px-3 py-2"></label>
                <label>Dauer (Min.)<input type="number" wire:model="form.duration_minutes" class="w-full mt-1 border border-slatecalm-200 rounded-xl px-3 py-2"></label>
            </div>
            <label>Benötigte Skills
                <select wire:model="form.required_skill_ids" multiple size="3" class="w-full mt-1 border border-slatecalm-200 rounded-xl px-3 py-2">
                    @foreach ($skills as $skill)
                        <option value="{{ $skill->id }}">{{ $skill->name }}</option>
                    @endforeach
                </select>
            </label>
            <label class="md:col-span-2">Hinweise<textarea wire:model="form.notes" rows="3" class="w-full mt-1 border border-slatecalm-200 rounded-xl px-3 py-2"></textarea></label>
            <div class="md:col-span-3 flex items-center justify-between">
                <p class="text-xs text-red-600">{{ $errors->first('form.*') }}</p>
                <button type="submit" class="px-5 py-2 bg-calm-600 hover:bg-calm-700 text-white rounded-xl font-medium">Einsatz anlegen</button>
            </div>
        </form>
    </section>
</div>
