<div class="p-6 max-w-3xl">
    <h1 class="text-xl font-semibold text-slatecalm-900 mb-1">KI-Einstellungen — {{ $team->name }}</h1>
    <p class="text-sm text-slate-500 mb-6">
        @if ($readOnly)
            Nur-Lese-Ansicht (System-Admin-Audit).
        @else
            Provider je Anwendungsfall konfigurierbar. Leerer API-Key behält den bisherigen bei.
        @endif
    </p>

    <div class="bg-white border border-slatecalm-200 rounded-2xl divide-y divide-slatecalm-200 mb-6">
        @foreach (\App\Models\AiSetting::USE_CASES as $case)
            <div class="p-4 flex items-center justify-between">
                <div>
                    <p class="font-medium text-slatecalm-900">{{ $case }}</p>
                    <p class="text-xs text-slate-500">
                        @if ($settings->has($case))
                            {{ $settings[$case]->provider }} · PII-Redaction: {{ $settings[$case]->redact_pii ? 'an' : 'aus' }}
                        @else
                            nicht konfiguriert (.env-Fallback)
                        @endif
                    </p>
                </div>
            </div>
        @endforeach
    </div>

    @if (! $readOnly)
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6 bg-white border border-slatecalm-200 rounded-2xl p-6">
            <div>
                <label class="text-xs font-medium text-slate-600">Anwendungsfall</label>
                <select wire:model="use_case" class="w-full mt-1 border border-slatecalm-200 rounded-xl px-3 py-2 text-sm">
                    @foreach (\App\Models\AiSetting::USE_CASES as $case)
                        <option value="{{ $case }}">{{ $case }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="text-xs font-medium text-slate-600">Provider</label>
                <select wire:model="provider" class="w-full mt-1 border border-slatecalm-200 rounded-xl px-3 py-2 text-sm">
                    @foreach (\App\Models\AiSetting::PROVIDERS as $p)
                        <option value="{{ $p }}">{{ $p }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="text-xs font-medium text-slate-600">API-Key</label>
                <input type="password" wire:model="api_key" class="w-full mt-1 border border-slatecalm-200 rounded-xl px-3 py-2 text-sm">
            </div>
            <div>
                <label class="text-xs font-medium text-slate-600">Endpoint (Ollama/Custom)</label>
                <input type="text" wire:model="endpoint_url" class="w-full mt-1 border border-slatecalm-200 rounded-xl px-3 py-2 text-sm">
            </div>
            <div>
                <label class="text-xs font-medium text-slate-600">Modell</label>
                <input type="text" wire:model="model" class="w-full mt-1 border border-slatecalm-200 rounded-xl px-3 py-2 text-sm">
            </div>
            <div class="flex items-center gap-2 mt-6">
                <input type="checkbox" wire:model="redact_pii" id="redact_pii">
                <label for="redact_pii" class="text-sm text-slate-600">PII vor Versand redigieren</label>
            </div>
            <div class="md:col-span-2 flex justify-end">
                <button wire:click="saveSetting" class="px-5 py-2 bg-calm-600 hover:bg-calm-700 text-white rounded-xl text-sm font-medium">Speichern</button>
            </div>
        </div>

        <div class="bg-white border border-slatecalm-200 rounded-2xl p-6">
            <h2 class="font-semibold text-slatecalm-900 mb-4">Monatliches Budget (€)</h2>
            <div class="flex gap-2">
                <input type="text" wire:model="monthly_limit_euros" class="flex-1 border border-slatecalm-200 rounded-xl px-3 py-2 text-sm">
                <button wire:click="saveBudget" class="px-5 py-2 bg-calm-600 hover:bg-calm-700 text-white rounded-xl text-sm font-medium">Speichern</button>
            </div>
            @error('monthly_limit_euros') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
        </div>
    @endif
</div>
