<div class="flex-1 overflow-y-auto p-6 space-y-6">
    <div>
        <h1 class="text-xl font-semibold text-slatecalm-900">E-Mail-Layout — {{ $team->name }}</h1>
        <p class="text-sm text-slate-500">{{ $readOnly ? 'Nur-Lese-Ansicht.' : 'Gilt für alle Antworten, die das Team per E-Mail an Kunden sendet.' }}</p>
    </div>

    @if ($status)
        <div class="text-sm text-calm-800 bg-calm-50 rounded-xl px-4 py-2" role="status">{{ $status }}</div>
    @endif

    <div class="grid grid-cols-1 xl:grid-cols-2 gap-6 items-start">
        <form wire:submit="save" class="bg-white border border-slatecalm-200 rounded-2xl p-6 space-y-4 text-sm">
            <fieldset @disabled($readOnly) class="space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <label class="block">Akzentfarbe
                        <span class="flex items-center gap-2 mt-1">
                            <input type="color" wire:model.live="accent_color" class="h-10 w-12 border border-slatecalm-200 rounded-lg">
                            <input type="text" wire:model.live.debounce.400ms="accent_color" maxlength="7" class="w-full border border-slatecalm-200 rounded-xl px-3 py-2">
                        </span>
                    </label>
                    <label class="block">Schriftart
                        <select wire:model.live="font" class="w-full mt-1 border border-slatecalm-200 rounded-xl px-3 py-2">
                            @foreach (\App\Models\TeamMailLayout::FONTS as $key => [$label])
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </label>
                </div>
                @error('accent_color') <p class="text-xs text-red-600">{{ $message }}</p> @enderror

                <div>
                    <label class="block">Logo <span class="text-slate-400">(PNG, JPG oder GIF, max. 200 KB, am besten transparentes PNG)</span>
                        <input type="file" wire:model="logo" accept="image/png,image/jpeg,image/gif" class="block w-full mt-1 text-sm">
                    </label>
                    @error('logo') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                    @if ($hasLogo && ! $readOnly)
                        <button type="button" wire:click="removeLogo" wire:confirm="Logo entfernen?" class="mt-2 text-xs px-3 py-1.5 rounded-lg bg-red-50 text-red-700">Logo entfernen</button>
                    @endif
                </div>

                <label class="block">Kopfzeile <span class="text-slate-400">(wird ohne Logo angezeigt, sonst als Alternativtext)</span>
                    <input type="text" wire:model.live.debounce.400ms="header_text" maxlength="255" class="w-full mt-1 border border-slatecalm-200 rounded-xl px-3 py-2">
                </label>
                @error('header_text') <p class="text-xs text-red-600">{{ $message }}</p> @enderror

                <label class="block">Signatur
                    <textarea wire:model.live.debounce.400ms="signature" rows="5" maxlength="2000" class="w-full mt-1 border border-slatecalm-200 rounded-xl px-3 py-2"></textarea>
                </label>
                @error('signature') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                <div class="text-xs text-slate-500">
                    <p class="mb-1">Platzhalter:</p>
                    <ul class="space-y-0.5">
                        @foreach (\App\Models\TeamMailLayout::PLACEHOLDERS as $placeholder => $description)
                            <li><code class="bg-slatecalm-100 rounded px-1">{{ $placeholder }}</code> {{ $description }}</li>
                        @endforeach
                    </ul>
                </div>

                <label class="block">Fußzeile <span class="text-slate-400">(z. B. Impressum, Anschrift)</span>
                    <textarea wire:model.live.debounce.400ms="footer_text" rows="4" maxlength="2000" class="w-full mt-1 border border-slatecalm-200 rounded-xl px-3 py-2"></textarea>
                </label>
                @error('footer_text') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
            </fieldset>

            @unless ($readOnly)
                <div class="flex justify-end">
                    <button type="submit" class="px-5 py-2 bg-calm-600 hover:bg-calm-700 text-white rounded-xl font-medium">Speichern</button>
                </div>
            @endunless
        </form>

        <div class="space-y-2" x-data="{ open: false, mobile: false }">
            <div class="flex items-center justify-between">
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wide">Vorschau</p>
                <button type="button" x-on:click="open = true" class="text-xs px-3 py-1.5 rounded-lg bg-calm-50 text-calm-800 hover:bg-calm-100 font-medium">Mailvorschau öffnen</button>
            </div>
            {{-- Empty sandbox: the preview is inert, no script or navigation from mail markup. --}}
            <iframe sandbox="" srcdoc="{{ $preview }}" title="Vorschau der E-Mail" class="w-full h-[640px] border border-slatecalm-200 rounded-2xl bg-white"></iframe>

            <div x-show="open" x-cloak x-transition.opacity x-on:keydown.escape.window="open = false" x-on:click.self="open = false"
                 class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 p-4">
                <div class="w-full max-w-4xl h-full max-h-[90vh] flex flex-col bg-white rounded-2xl shadow-xl overflow-hidden" role="dialog" aria-modal="true" aria-labelledby="mail-preview-title">
                    <div class="flex items-center justify-between gap-4 px-6 py-4 border-b border-slatecalm-200">
                        <h2 id="mail-preview-title" class="font-semibold text-slatecalm-900">Mailvorschau</h2>
                        <div class="flex items-center gap-2 text-xs">
                            <button type="button" x-on:click="mobile = false" x-bind:class="mobile ? 'bg-slatecalm-100 text-slate-600' : 'bg-calm-600 text-white'" class="px-3 py-1.5 rounded-lg">Desktop</button>
                            <button type="button" x-on:click="mobile = true" x-bind:class="mobile ? 'bg-calm-600 text-white' : 'bg-slatecalm-100 text-slate-600'" class="px-3 py-1.5 rounded-lg">Mobil</button>
                            <button type="button" x-on:click="open = false" class="ml-2 px-3 py-1.5 rounded-lg bg-slatecalm-100 text-slate-700 hover:bg-slatecalm-200" aria-label="Vorschau schließen">Schließen</button>
                        </div>
                    </div>
                    <dl class="grid grid-cols-[auto_1fr] gap-x-3 gap-y-1 px-6 py-3 text-xs border-b border-slatecalm-200 bg-slatecalm-50">
                        <dt class="text-slate-500">Von</dt><dd class="text-slatecalm-900">{{ $team->name }} &lt;support@example.com&gt;</dd>
                        <dt class="text-slate-500">An</dt><dd class="text-slatecalm-900">Frau Muster &lt;kunde@example.com&gt;</dd>
                        <dt class="text-slate-500">Betreff</dt><dd class="text-slatecalm-900">[Ticket #1234] Ihre Anfrage</dd>
                    </dl>
                    <div class="flex-1 overflow-auto bg-slatecalm-100 p-4 flex justify-center">
                        <iframe sandbox="" srcdoc="{{ $preview }}" title="Mailvorschau im Vollbild" x-bind:class="mobile ? 'w-[375px]' : 'w-full'" class="h-full min-h-[480px] border border-slatecalm-200 rounded-xl bg-white"></iframe>
                    </div>
                    @if ($logo)
                        <p class="px-6 py-2 text-xs text-slate-500 border-t border-slatecalm-200">Das neu ausgewählte Logo erscheint nach dem Speichern in der Vorschau.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
