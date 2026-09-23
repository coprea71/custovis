<div class="p-6 max-w-3xl">
    <h1 class="text-xl font-semibold text-slatecalm-900 mb-1">WhatsApp — {{ $team->name }}</h1>
    <p class="text-sm text-slate-500 mb-6">
        @if ($readOnly)
            Nur-Lese-Ansicht (System-Admin-Audit).
        @else
            Anbindung über die offizielle Meta WhatsApp Business Cloud API.
        @endif
    </p>

    @if ($connectionTestResult)
        <div class="mb-6 p-3 rounded-xl bg-slatecalm-100 text-sm text-slate-700">{{ $connectionTestResult }}</div>
    @endif

    @if (! $readOnly)
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6 bg-white border border-slatecalm-200 rounded-2xl p-6">
            <div>
                <label class="text-xs font-medium text-slate-600">Anzeigename</label>
                <input type="text" wire:model="display_name" class="w-full mt-1 border border-slatecalm-200 rounded-xl px-3 py-2 text-sm">
                @error('display_name') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="text-xs font-medium text-slate-600">Phone Number ID</label>
                <input type="text" wire:model="phone_number_id" class="w-full mt-1 border border-slatecalm-200 rounded-xl px-3 py-2 text-sm">
                @error('phone_number_id') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="text-xs font-medium text-slate-600">Business Account ID</label>
                <input type="text" wire:model="business_account_id" class="w-full mt-1 border border-slatecalm-200 rounded-xl px-3 py-2 text-sm">
                @error('business_account_id') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="text-xs font-medium text-slate-600">Access Token</label>
                <input type="password" wire:model="access_token" class="w-full mt-1 border border-slatecalm-200 rounded-xl px-3 py-2 text-sm">
                @error('access_token') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="text-xs font-medium text-slate-600">Webhook Verify Token</label>
                <input type="text" wire:model="webhook_verify_token" class="w-full mt-1 border border-slatecalm-200 rounded-xl px-3 py-2 text-sm">
                @error('webhook_verify_token') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="text-xs font-medium text-slate-600">App Secret</label>
                <input type="password" wire:model="app_secret" class="w-full mt-1 border border-slatecalm-200 rounded-xl px-3 py-2 text-sm">
                @error('app_secret') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>
            <div class="md:col-span-2 flex justify-end">
                <button wire:click="createAccount" class="px-5 py-2 bg-calm-600 hover:bg-calm-700 text-white rounded-xl text-sm font-medium">Verbindung anlegen</button>
            </div>
        </div>
    @endif

    <div class="bg-white border border-slatecalm-200 rounded-2xl divide-y divide-slatecalm-200">
        @forelse ($accounts as $account)
            <div class="p-4 flex items-center justify-between">
                <div>
                    <p class="font-medium text-slatecalm-900">{{ $account->display_name }}</p>
                    <p class="text-xs text-slate-500">Phone Number ID: {{ $account->phone_number_id }} · {{ $account->active ? 'Aktiv' : 'Inaktiv' }}</p>
                </div>
                <div class="flex items-center gap-2">
                    <button wire:click="testConnection({{ $account->id }})" class="text-xs px-3 py-1.5 rounded-lg font-medium bg-slatecalm-100 text-slate-700 hover:bg-slatecalm-200">
                        Verbindung testen
                    </button>
                    @if (! $readOnly)
                        <button wire:click="toggleActive({{ $account->id }})" class="text-xs px-3 py-1.5 rounded-lg font-medium {{ $account->active ? 'bg-calm-100 text-calm-800' : 'bg-slatecalm-100 text-slate-500' }}">
                            {{ $account->active ? 'Aktiv' : 'Inaktiv' }}
                        </button>
                    @endif
                </div>
            </div>
        @empty
            <p class="p-4 text-sm text-slate-400">Noch keine WhatsApp-Verbindung angelegt.</p>
        @endforelse
    </div>
</div>
