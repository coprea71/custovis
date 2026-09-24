<div class="space-y-6">
    <div class="flex items-center justify-between">
        <h1 class="text-xl font-semibold text-slatecalm-900">Teams</h1>
        <button type="button" wire:click="newTeam" class="px-4 py-2 bg-calm-600 hover:bg-calm-700 text-white rounded-xl text-sm font-medium">Neues Team</button>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <section class="bg-white border border-slatecalm-200 rounded-2xl p-3 space-y-1">
            @forelse ($teams as $item)
                <button type="button" wire:key="t-{{ $item->id }}" wire:click="select({{ $item->id }})"
                        class="w-full text-left px-3 py-2 rounded-lg text-sm flex justify-between {{ $selectedId === $item->id ? 'bg-calm-100 text-calm-800' : 'hover:bg-slatecalm-100' }}">
                    <span>{{ $item->name }}</span><span class="text-xs text-slate-400">{{ $item->users_count }}</span>
                </button>
            @empty
                <p class="p-2 text-sm text-slate-400">Noch keine Teams.</p>
            @endforelse
        </section>

        <section class="md:col-span-2 space-y-4">
            <form wire:submit="save" class="bg-white border border-slatecalm-200 rounded-2xl p-5 space-y-3 text-sm">
                <h2 class="font-semibold text-slatecalm-900">{{ $team ? 'Team bearbeiten' : 'Team anlegen' }}</h2>
                <label class="block">Name<input type="text" wire:model="name" class="w-full mt-1 border border-slatecalm-200 rounded-xl px-3 py-2"></label>
                @error('name') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                <label class="block">Beschreibung<input type="text" wire:model="description" class="w-full mt-1 border border-slatecalm-200 rounded-xl px-3 py-2"></label>
                <div class="flex justify-between items-center">
                    @if ($team)
                        <a href="{{ route('agent.team.settings', $team) }}" class="text-xs text-ocean-700 hover:underline">Team-Einstellungen (API-Keys, WhatsApp, KI …)</a>
                    @else
                        <span></span>
                    @endif
                    <button type="submit" class="px-5 py-2 bg-calm-600 hover:bg-calm-700 text-white rounded-xl font-medium">Speichern</button>
                </div>
            </form>

            @if ($team)
                <div class="bg-white border border-slatecalm-200 rounded-2xl p-5 space-y-3 text-sm">
                    <h2 class="font-semibold text-slatecalm-900">Mitglieder</h2>
                    @forelse ($team->users as $member)
                        <div wire:key="m-{{ $member->id }}" class="flex flex-wrap items-center justify-between gap-2">
                            <span>{{ $member->name }} <span class="text-xs text-slate-400">{{ $member->email }}</span></span>
                            <span class="flex items-center gap-2">
                                <select wire:change="changeRole({{ $member->id }}, $event.target.value)" aria-label="Rolle im Team" class="border border-slatecalm-200 rounded-lg px-2 py-1 text-xs">
                                    @foreach (\App\Livewire\Admin\TeamManager::TEAM_ROLES as $key => $label)
                                        <option value="{{ $key }}" @selected($member->pivot->role_in_team === $key)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                <button type="button" wire:click="removeMember({{ $member->id }})" wire:confirm="Aus dem Team entfernen?" class="text-xs text-red-600">Entfernen</button>
                            </span>
                        </div>
                    @empty
                        <p class="text-slate-400">Noch keine Mitglieder.</p>
                    @endforelse

                    <div class="flex flex-wrap gap-2 pt-3 border-t border-slatecalm-200">
                        <select wire:model="newMemberId" aria-label="Nutzer hinzufügen" class="flex-1 border border-slatecalm-200 rounded-xl px-2 py-1.5">
                            <option value="">Nutzer hinzufügen...</option>
                            @foreach ($candidates as $candidate)
                                <option value="{{ $candidate->id }}">{{ $candidate->name }}</option>
                            @endforeach
                        </select>
                        <select wire:model="newMemberRole" aria-label="Rolle" class="border border-slatecalm-200 rounded-xl px-2 py-1.5">
                            @foreach (\App\Livewire\Admin\TeamManager::TEAM_ROLES as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        <button type="button" wire:click="addMember" class="px-3 py-1.5 bg-calm-600 text-white rounded-xl">Hinzufügen</button>
                    </div>
                    @error('newMemberId') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
            @endif
        </section>
    </div>
</div>
