<div class="space-y-6">
    <div class="flex items-center justify-between gap-4">
        <h1 class="text-xl font-semibold text-slatecalm-900">Kunden</h1>
        @can('compliance.manage')
            <a href="{{ route('admin.compliance') }}" class="px-4 py-2 bg-slatecalm-100 text-slate-700 rounded-xl text-sm font-medium">DSGVO-Werkzeuge</a>
        @endcan
    </div>

    @if ($status)
        <div class="text-sm text-calm-800 bg-calm-50 rounded-xl px-4 py-2" role="status">{{ $status }}</div>
    @endif

    <input type="search" wire:model.live.debounce.300ms="search" placeholder="Name oder E-Mail suchen..." maxlength="255" class="w-full border border-slatecalm-200 rounded-xl px-3 py-2 text-sm">

    <div class="bg-white border border-slatecalm-200 rounded-2xl divide-y divide-slatecalm-200">
        @forelse ($customers as $customer)
            <div wire:key="customer-{{ $customer->id }}" class="p-4 flex flex-wrap items-center justify-between gap-3">
                <div>
                    <p class="font-medium text-slatecalm-900">{{ $customer->name }}</p>
                    <p class="text-xs text-slate-400">{{ $customer->email }} · {{ $customer->tickets_count }} Ticket(s)</p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <button wire:click="edit({{ $customer->id }})" class="text-xs px-3 py-1.5 rounded-lg font-medium bg-slatecalm-100 text-slate-700">Bearbeiten</button>
                    @if ($customer->active)
                        <button wire:click="invite({{ $customer->id }})" class="text-xs px-3 py-1.5 rounded-lg font-medium bg-slatecalm-100 text-slate-700">Einladung senden</button>
                    @endif
                    <button wire:click="toggleActive({{ $customer->id }})" wire:confirm="{{ $customer->active ? 'Portal-Zugang wirklich sperren?' : 'Portal-Zugang wieder freigeben?' }}"
                            class="text-xs px-3 py-1.5 rounded-lg font-medium {{ $customer->active ? 'bg-calm-100 text-calm-800' : 'bg-red-50 text-red-700' }}">
                        {{ $customer->active ? 'Aktiv' : 'Gesperrt' }}
                    </button>
                </div>
            </div>
        @empty
            <p class="p-4 text-sm text-slate-400">Keine Kunden gefunden.</p>
        @endforelse
    </div>

    {{ $customers->links() }}

    <div class="bg-white border border-slatecalm-200 rounded-2xl p-6">
        <h2 class="font-semibold text-slatecalm-900 mb-1">{{ $editingId ? 'Kunde bearbeiten' : 'Neuer Kunde' }}</h2>
        @unless ($editingId)
            <p class="text-xs text-slate-500 mb-4">Bestehende Tickets mit derselben E-Mail-Adresse werden automatisch zugeordnet. Das Passwort legt der Kunde über den Einladungs-Link selbst fest.</p>
        @endunless
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
            <div>
                <label for="customer-name" class="text-xs font-medium text-slate-600">Name</label>
                <input id="customer-name" type="text" wire:model="name" maxlength="255" class="w-full mt-1 border border-slatecalm-200 rounded-xl px-3 py-2 text-sm">
                @error('name') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="customer-email" class="text-xs font-medium text-slate-600">E-Mail</label>
                <input id="customer-email" type="email" wire:model="email" maxlength="255" class="w-full mt-1 border border-slatecalm-200 rounded-xl px-3 py-2 text-sm">
                @error('email') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>
        </div>
        <div class="flex justify-end gap-2 mt-4">
            @if ($editingId)
                <button wire:click="cancelEdit" class="px-5 py-2 bg-slatecalm-100 text-slate-700 rounded-xl text-sm font-medium">Abbrechen</button>
            @endif
            <button wire:click="save" class="px-5 py-2 bg-calm-600 hover:bg-calm-700 text-white rounded-xl text-sm font-medium">{{ $editingId ? 'Speichern' : 'Anlegen' }}</button>
        </div>
    </div>
</div>
