<div class="flex-1 overflow-y-auto p-6 space-y-6">
    <div>
        <h1 class="text-xl font-semibold text-slatecalm-900">Automatische E-Mails — {{ $team->name }}</h1>
        <p class="text-sm text-slate-500">{{ $readOnly ? 'Nur-Lese-Ansicht.' : 'Gilt für Tickets, die per E-Mail eingehen. Die Mails nutzen das E-Mail-Layout des Teams und erscheinen im Ticketverlauf.' }}</p>
    </div>

    @if ($status)
        <div class="text-sm text-calm-800 bg-calm-50 rounded-xl px-4 py-2" role="status">{{ $status }}</div>
    @endif

    <form wire:submit="save" class="space-y-4 text-sm max-w-3xl">
        <fieldset @disabled($readOnly) class="space-y-4">
            @foreach (\App\Models\TeamMailLayout::AUTO_MAILS as $type => [$label])
                <div class="bg-white border border-slatecalm-200 rounded-2xl p-6 space-y-3">
                    <div class="flex items-center justify-between gap-4">
                        <label class="flex items-center gap-2 font-medium text-slatecalm-900">
                            <input type="checkbox" wire:model="enabled.{{ $type }}" class="rounded border-slatecalm-300">
                            {{ $label }}
                        </label>
                        @unless ($readOnly)
                            <button type="button" wire:click="resetText('{{ $type }}')" class="text-xs px-3 py-1.5 rounded-lg bg-slatecalm-100 text-slate-700 hover:bg-slatecalm-200">Standardtext</button>
                        @endunless
                    </div>
                    <p class="text-xs text-slate-500">
                        @switch($type)
                            @case('acknowledgement') Geht an den Absender, sobald aus seiner E-Mail ein neues Ticket entsteht. @break
                            @case('resolved') Geht an den Kunden, sobald das Ticket auf „Geschlossen“ gesetzt wird. @break
                            @case('status_change') Geht bei jedem Statuswechsel – aber nur an Kunden, bei denen in der Kundenverwaltung „Mail bei Statuswechsel“ aktiviert ist. @break
                        @endswitch
                    </p>
                    <textarea wire:model="texts.{{ $type }}" rows="6" maxlength="5000" aria-label="Text: {{ $label }}" class="w-full border border-slatecalm-200 rounded-xl px-3 py-2"></textarea>
                    @error("texts.{$type}") <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
            @endforeach

            <div class="text-xs text-slate-500">
                <p class="mb-1">Platzhalter:</p>
                <ul class="space-y-0.5">
                    @foreach (\App\Models\TeamMailLayout::PLACEHOLDERS as $placeholder => $description)
                        <li><code class="bg-slatecalm-100 rounded px-1">{{ $placeholder }}</code> {{ $description }}</li>
                    @endforeach
                </ul>
            </div>
        </fieldset>

        @unless ($readOnly)
            <div class="flex justify-end">
                <button type="submit" class="px-5 py-2 bg-calm-600 hover:bg-calm-700 text-white rounded-xl font-medium">Speichern</button>
            </div>
        @endunless
    </form>
</div>
