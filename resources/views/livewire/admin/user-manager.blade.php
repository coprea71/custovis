<div class="space-y-6">
    <div class="flex items-center justify-between gap-3">
        <h1 class="text-xl font-semibold text-slatecalm-900">Nutzer</h1>
        <button type="button" wire:click="newUser" class="px-4 py-2 bg-calm-600 hover:bg-calm-700 text-white rounded-xl text-sm font-medium">Neuer Nutzer</button>
    </div>

    @if ($status)
        <p class="text-sm rounded-xl px-4 py-3 bg-calm-50 text-calm-800">{{ $status }}</p>
    @endif
    @error('user') <p class="text-sm rounded-xl px-4 py-3 bg-red-50 text-red-700">{{ $message }}</p> @enderror

    <form wire:submit="save" class="bg-white border border-slatecalm-200 rounded-2xl p-6 space-y-4">
        <h2 class="font-semibold text-slatecalm-900">{{ $editingId ? 'Nutzer bearbeiten' : 'Nutzer anlegen' }}</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
            <label>Name<input type="text" wire:model="form.name" class="w-full mt-1 border border-slatecalm-200 rounded-xl px-3 py-2"></label>
            <label>E-Mail<input type="email" wire:model="form.email" class="w-full mt-1 border border-slatecalm-200 rounded-xl px-3 py-2"></label>
        </div>
        <fieldset class="text-sm">
            <legend class="text-slate-600 mb-1">Rollen</legend>
            <div class="flex flex-wrap gap-3">
                @foreach ($roles as $role)
                    <label class="flex items-center gap-1.5"><input type="checkbox" wire:model="form.roles" value="{{ $role }}"> {{ $role }}</label>
                @endforeach
            </div>
        </fieldset>
        <p class="text-xs text-red-600">{{ $errors->first('form.*') }}</p>
        <div class="flex justify-between items-center">
            <p class="text-xs text-slate-400">{{ $editingId ? '' : 'Das Passwort legt der Nutzer über den Einladungslink selbst fest.' }}</p>
            <button type="submit" class="px-5 py-2 bg-calm-600 hover:bg-calm-700 text-white rounded-xl text-sm font-medium">Speichern</button>
        </div>
    </form>

    <input type="search" wire:model.live.debounce.300ms="search" placeholder="Name oder E-Mail suchen..." aria-label="Nutzer suchen" class="w-full border border-slatecalm-200 rounded-xl px-3 py-2 text-sm">

    <div class="bg-white border border-slatecalm-200 rounded-2xl divide-y divide-slatecalm-200">
        @foreach ($users as $user)
            <div wire:key="u-{{ $user->id }}" class="p-4 flex flex-wrap items-center justify-between gap-3">
                <div class="min-w-0">
                    <p class="font-medium text-slatecalm-900">{{ $user->name }}
                        @unless ($user->active) <span class="ml-1 text-xs px-2 py-0.5 rounded bg-red-50 text-red-700">deaktiviert</span> @endunless
                    </p>
                    <p class="text-xs text-slate-500">{{ $user->email }} · {{ $user->roles->pluck('name')->join(', ') ?: 'keine Rolle' }}
                        · {{ $user->teams->pluck('name')->join(', ') ?: 'kein Team' }}
                        · 2FA {{ $user->two_factor_confirmed_at ? 'aktiv' : 'nicht eingerichtet' }}</p>
                </div>
                <div class="flex flex-wrap gap-2 text-xs">
                    <button type="button" wire:click="edit({{ $user->id }})" class="px-3 py-1.5 rounded-lg bg-slatecalm-100 text-slate-700">Bearbeiten</button>
                    <button type="button" wire:click="invite({{ $user->id }})" class="px-3 py-1.5 rounded-lg bg-slatecalm-100 text-slate-700">Passwort-Link senden</button>
                    @if ($user->two_factor_confirmed_at)
                        <button type="button" wire:click="resetTwoFactor({{ $user->id }})" wire:confirm="2FA dieses Nutzers zurücksetzen?" class="px-3 py-1.5 rounded-lg bg-ocean-50 text-ocean-700">2FA zurücksetzen</button>
                    @endif
                    <button type="button" wire:click="toggleActive({{ $user->id }})" wire:confirm="{{ $user->active ? 'Nutzer deaktivieren? Laufende Sitzungen werden beendet.' : 'Nutzer wieder aktivieren?' }}"
                            class="px-3 py-1.5 rounded-lg {{ $user->active ? 'bg-red-50 text-red-700' : 'bg-calm-100 text-calm-800' }}">{{ $user->active ? 'Deaktivieren' : 'Aktivieren' }}</button>
                </div>
            </div>
        @endforeach
    </div>

    {{ $users->links() }}
</div>
