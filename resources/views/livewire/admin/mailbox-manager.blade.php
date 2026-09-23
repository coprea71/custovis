<div class="space-y-6">
    <h1 class="text-xl font-semibold text-slatecalm-900">Mailboxen</h1>

    <div class="bg-white border border-slatecalm-200 rounded-2xl divide-y divide-slatecalm-200">
        @forelse ($mailboxes as $mailbox)
            <div class="p-4 flex items-center justify-between">
                <div>
                    <p class="font-medium text-slatecalm-900">{{ $mailbox->name }} <span class="text-xs text-slate-400">({{ $mailbox->team->name }})</span></p>
                    <p class="text-sm text-slate-500">{{ $mailbox->email_address }} — {{ $mailbox->imap_host }}</p>
                    @if ($mailbox->last_fetched_at)
                        <p class="text-xs text-slate-400">Zuletzt abgerufen: {{ $mailbox->last_fetched_at->diffForHumans() }}</p>
                    @endif
                </div>
                <div class="flex items-center gap-2">
                    <button wire:click="toggleActive({{ $mailbox->id }})" class="text-xs px-3 py-1.5 rounded-lg font-medium {{ $mailbox->active ? 'bg-calm-100 text-calm-800' : 'bg-slatecalm-100 text-slate-500' }}">
                        {{ $mailbox->active ? 'Aktiv' : 'Inaktiv' }}
                    </button>
                    <button wire:click="edit({{ $mailbox->id }})" class="text-xs px-3 py-1.5 rounded-lg font-medium bg-slatecalm-100 text-slate-700 hover:bg-slatecalm-200">
                        Bearbeiten
                    </button>
                </div>
            </div>
        @empty
            <p class="p-4 text-sm text-slate-400">Noch keine Mailbox angelegt.</p>
        @endforelse
    </div>

    <div class="bg-white border border-slatecalm-200 rounded-2xl p-6">
        <h2 class="font-semibold text-slatecalm-900 mb-4">{{ $editingId ? 'Mailbox bearbeiten' : 'Neue Mailbox' }}</h2>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="text-xs font-medium text-slate-600">Team</label>
                <select wire:model="team_id" class="w-full mt-1 border border-slatecalm-200 rounded-xl px-3 py-2 text-sm">
                    <option value="0">— Team wählen —</option>
                    @foreach ($teams as $team)
                        <option value="{{ $team->id }}">{{ $team->name }}</option>
                    @endforeach
                </select>
                @error('team_id') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="text-xs font-medium text-slate-600">Name</label>
                <input type="text" wire:model="name" class="w-full mt-1 border border-slatecalm-200 rounded-xl px-3 py-2 text-sm">
                @error('name') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="text-xs font-medium text-slate-600">E-Mail-Adresse</label>
                <input type="email" wire:model="email_address" class="w-full mt-1 border border-slatecalm-200 rounded-xl px-3 py-2 text-sm">
                @error('email_address') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>

            <div></div>

            <div>
                <label class="text-xs font-medium text-slate-600">IMAP-Host</label>
                <input type="text" wire:model="imap_host" class="w-full mt-1 border border-slatecalm-200 rounded-xl px-3 py-2 text-sm">
                @error('imap_host') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>
            <div class="grid grid-cols-2 gap-2">
                <div>
                    <label class="text-xs font-medium text-slate-600">Port</label>
                    <input type="number" wire:model="imap_port" class="w-full mt-1 border border-slatecalm-200 rounded-xl px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="text-xs font-medium text-slate-600">Verschlüsselung</label>
                    <select wire:model="imap_encryption" class="w-full mt-1 border border-slatecalm-200 rounded-xl px-3 py-2 text-sm">
                        <option value="ssl">SSL</option>
                        <option value="tls">TLS</option>
                        <option value="none">Keine</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="text-xs font-medium text-slate-600">IMAP-Benutzername</label>
                <input type="text" wire:model="imap_username" class="w-full mt-1 border border-slatecalm-200 rounded-xl px-3 py-2 text-sm">
                @error('imap_username') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="text-xs font-medium text-slate-600">IMAP-Passwort {{ $editingId ? '(leer lassen zum Beibehalten)' : '' }}</label>
                <input type="password" wire:model="imap_password" class="w-full mt-1 border border-slatecalm-200 rounded-xl px-3 py-2 text-sm">
                @error('imap_password') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="text-xs font-medium text-slate-600">SMTP-Host</label>
                <input type="text" wire:model="smtp_host" class="w-full mt-1 border border-slatecalm-200 rounded-xl px-3 py-2 text-sm">
                @error('smtp_host') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>
            <div class="grid grid-cols-2 gap-2">
                <div>
                    <label class="text-xs font-medium text-slate-600">Port</label>
                    <input type="number" wire:model="smtp_port" class="w-full mt-1 border border-slatecalm-200 rounded-xl px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="text-xs font-medium text-slate-600">Verschlüsselung</label>
                    <select wire:model="smtp_encryption" class="w-full mt-1 border border-slatecalm-200 rounded-xl px-3 py-2 text-sm">
                        <option value="ssl">SSL</option>
                        <option value="tls">TLS</option>
                        <option value="none">Keine</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="text-xs font-medium text-slate-600">SMTP-Benutzername</label>
                <input type="text" wire:model="smtp_username" class="w-full mt-1 border border-slatecalm-200 rounded-xl px-3 py-2 text-sm">
                @error('smtp_username') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="text-xs font-medium text-slate-600">SMTP-Passwort {{ $editingId ? '(leer lassen zum Beibehalten)' : '' }}</label>
                <input type="password" wire:model="smtp_password" class="w-full mt-1 border border-slatecalm-200 rounded-xl px-3 py-2 text-sm">
                @error('smtp_password') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>
        </div>

        <div class="flex justify-end gap-2 mt-6">
            @if ($editingId)
                <button wire:click="resetForm" class="text-sm px-4 py-2 rounded-xl bg-slatecalm-100 text-slate-700">Abbrechen</button>
            @endif
            <button wire:click="save" class="text-sm px-5 py-2 rounded-xl bg-calm-600 hover:bg-calm-700 text-white font-medium">Speichern</button>
        </div>
    </div>
</div>
