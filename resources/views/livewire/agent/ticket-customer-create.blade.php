<div>
    @if ($status)
        <p class="text-xs text-calm-700" role="status">{{ $status }}</p>
    @else
        <button type="button" wire:click="openModal"
                class="w-full text-xs px-3 py-2 rounded-lg bg-ocean-50 text-ocean-700 font-medium">
            Kunde anlegen
        </button>
    @endif

    @if ($open)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 p-4" wire:keydown.escape.window="closeModal">
            <div class="w-full max-w-md bg-white rounded-2xl shadow-xl p-6" role="dialog" aria-modal="true" aria-labelledby="customer-create-title">
                <h2 id="customer-create-title" class="font-semibold text-slatecalm-900 mb-1">Kunde anlegen</h2>
                <p class="text-xs text-slate-500 mb-4">Der Kunde erhält keinen Portal-Zugang, bis ihm eine Einladung gesendet wird.</p>

                <form wire:submit="save" class="space-y-3">
                    <div>
                        <label for="customer-create-name" class="text-xs font-medium text-slate-600">Name</label>
                        <input id="customer-create-name" type="text" wire:model="name" maxlength="255" class="w-full mt-1 border border-slatecalm-200 rounded-xl px-3 py-2 text-sm">
                        @error('name') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="customer-create-email" class="text-xs font-medium text-slate-600">E-Mail-Adresse</label>
                        <input id="customer-create-email" type="email" wire:model="email" maxlength="255" class="w-full mt-1 border border-slatecalm-200 rounded-xl px-3 py-2 text-sm">
                        @error('email') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" wire:click="closeModal" class="text-sm px-4 py-2 rounded-xl bg-slatecalm-100 text-slate-700">Abbrechen</button>
                        <button type="submit" class="text-sm px-5 py-2 rounded-xl bg-calm-600 hover:bg-calm-700 text-white font-medium">Anlegen</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
