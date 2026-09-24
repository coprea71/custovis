<x-layouts.app title="Techniker" :scripts="false">
    <x-slot:head>
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="theme-color" content="#3D5E50">
        <link rel="manifest" href="/field-manifest.webmanifest">
        @vite(['resources/js/field.js'])
    </x-slot:head>

    <div x-data="fieldApp" x-cloak class="max-w-xl mx-auto min-h-full flex flex-col">
        <header class="sticky top-0 z-10 bg-white border-b border-slatecalm-200 px-4 py-3 flex items-center justify-between">
            <div>
                <p class="font-semibold text-slatecalm-900 text-sm">{{ $technician->user->name }}</p>
                <p class="text-xs" :class="online ? 'text-calm-600' : 'text-red-600'"
                   x-text="online ? 'Online' + (queue.length ? ' · ' + queue.length + ' ausstehend' : '') : 'Offline · ' + queue.length + ' Aktion(en) in Warteschlange'"></p>
            </div>
            <button type="button" @click="flush(); load()" class="text-xs px-3 py-1.5 rounded-lg bg-slatecalm-100 text-slate-700">Aktualisieren</button>
        </header>

        <template x-for="notice in notices" :key="notice.operation_uuid">
            <p class="mx-4 mt-3 text-xs rounded-lg px-3 py-2 bg-ocean-50 text-ocean-700" x-text="notice.message"></p>
        </template>

        {{-- Day list --}}
        <main class="flex-1 p-4 space-y-3" x-show="!selected">
            <template x-for="appointment in appointments" :key="appointment.id">
                <button type="button" @click="selectedId = appointment.id" class="w-full text-left bg-white border border-slatecalm-200 rounded-2xl p-4">
                    <div class="flex justify-between text-xs text-slate-500">
                        <span x-text="formatTime(appointment.start) + ' – ' + formatTime(appointment.end)"></span>
                        <span x-text="appointment.state_label"></span>
                    </div>
                    <p class="mt-1 font-medium text-slatecalm-900" x-text="appointment.ticket.subject"></p>
                    <p class="text-sm text-slate-500" x-text="appointment.address"></p>
                    <p class="text-xs text-ocean-700 mt-1" x-show="appointment.kind === 'delivery'">Warenauslieferung</p>
                </button>
            </template>
            <p x-show="appointments.length === 0" class="text-sm text-slate-400">Heute keine Einsätze.</p>
        </main>

        {{-- Appointment detail --}}
        <template x-if="selected">
            <main class="flex-1 p-4 space-y-4">
                <button type="button" @click="selectedId = null" class="text-sm text-slate-500">&larr; Tagesliste</button>

                <section class="bg-white border border-slatecalm-200 rounded-2xl p-4 space-y-2">
                    <p class="font-semibold text-slatecalm-900" x-text="'#' + selected.ticket.id + ' ' + selected.ticket.subject"></p>
                    <p class="text-sm text-slate-600" x-text="selected.address"></p>
                    <p class="text-sm text-slate-600" x-show="selected.ticket.requester_name" x-text="selected.ticket.requester_name + (selected.ticket.requester_phone ? ' · ' + selected.ticket.requester_phone : '')"></p>
                    <p class="text-sm text-slate-500 whitespace-pre-line" x-show="selected.notes" x-text="selected.notes"></p>
                    <a x-show="selected.lat" :href="'geo:' + selected.lat + ',' + selected.lng" class="inline-block text-xs text-ocean-700 underline">Navigation öffnen</a>
                    <p class="text-sm">Status: <strong x-text="selected.state_label"></strong></p>
                    <div class="flex flex-wrap gap-2">
                        <template x-for="next in selected.next_states" :key="next.key">
                            <button type="button" @click="changeState(selected, next)" class="px-3 py-2 rounded-xl text-sm font-medium bg-calm-600 text-white" x-text="next.label"></button>
                        </template>
                    </div>
                </section>

                <section class="bg-white border border-slatecalm-200 rounded-2xl p-4" x-show="selected.checklist_items.length">
                    <h2 class="text-sm font-semibold text-slatecalm-900 mb-2">Checkliste</h2>
                    <template x-for="item in selected.checklist_items" :key="item.id">
                        <label class="flex items-center gap-3 py-2 text-sm">
                            <input type="checkbox" :checked="item.checked" @change="toggleItem(selected, item)" class="w-5 h-5">
                            <span x-text="item.label"></span>
                        </label>
                    </template>
                </section>

                <section class="bg-white border border-slatecalm-200 rounded-2xl p-4">
                    <h2 class="text-sm font-semibold text-slatecalm-900 mb-2">Material / Ersatzteile</h2>
                    <form @submit.prevent="addPart(selected, $el)" class="flex gap-2">
                        <input name="description" placeholder="Bezeichnung" class="flex-1 border border-slatecalm-200 rounded-xl px-3 py-2 text-sm">
                        <input name="quantity" type="number" step="0.01" min="0.01" value="1" class="w-20 border border-slatecalm-200 rounded-xl px-2 py-2 text-sm">
                        <button class="px-3 py-2 rounded-xl text-sm bg-slatecalm-100">+</button>
                    </form>
                </section>

                <section class="bg-white border border-slatecalm-200 rounded-2xl p-4" x-show="selected.kind !== 'delivery'">
                    <h2 class="text-sm font-semibold text-slatecalm-900 mb-2">Kundenunterschrift</h2>
                    <form @submit.prevent="sign(selected, $el, $refs.signPad)" class="space-y-2">
                        <input name="signer_name" placeholder="Name des Kunden" class="w-full border border-slatecalm-200 rounded-xl px-3 py-2 text-sm">
                        <canvas x-ref="signPad" x-signature-pad width="600" height="200" class="w-full border border-slatecalm-200 rounded-xl touch-none"></canvas>
                        <div class="flex justify-between">
                            <button type="button" @click="clearSignaturePad($refs.signPad)" class="text-xs text-slate-500">Löschen</button>
                            <button class="px-4 py-2 rounded-xl text-sm font-medium bg-calm-600 text-white">Unterschrift speichern</button>
                        </div>
                    </form>
                </section>

                <section class="bg-white border border-slatecalm-200 rounded-2xl p-4" x-show="selected.kind === 'delivery'">
                    <h2 class="text-sm font-semibold text-slatecalm-900 mb-2">Warenauslieferung</h2>
                    <p x-show="selected.delivered" class="text-sm text-calm-700">Empfang bestätigt.</p>
                    <form x-show="!selected.delivered" @submit.prevent="deliver(selected, $el, $refs.deliveryPad)" class="space-y-2">
                        <input name="delivery_note_number" placeholder="Lieferscheinnummer *" class="w-full border border-slatecalm-200 rounded-xl px-3 py-2 text-sm">
                        <textarea name="items_text" rows="2" placeholder="Positionen (optional, zusätzlich zu erfasstem Material)" class="w-full border border-slatecalm-200 rounded-xl px-3 py-2 text-sm"></textarea>
                        <input name="recipient_name" placeholder="Name des Empfängers *" class="w-full border border-slatecalm-200 rounded-xl px-3 py-2 text-sm">
                        <input name="recipient_email" type="email" placeholder="E-Mail (optional)" class="w-full border border-slatecalm-200 rounded-xl px-3 py-2 text-sm">
                        <input name="recipient_phone" type="tel" placeholder="Telefon (optional)" class="w-full border border-slatecalm-200 rounded-xl px-3 py-2 text-sm">
                        <canvas x-ref="deliveryPad" x-signature-pad width="600" height="200" class="w-full border border-slatecalm-200 rounded-xl touch-none"></canvas>
                        <div class="flex justify-between">
                            <button type="button" @click="clearSignaturePad($refs.deliveryPad)" class="text-xs text-slate-500">Löschen</button>
                            <button class="px-4 py-2 rounded-xl text-sm font-medium bg-calm-600 text-white">Empfang bestätigen</button>
                        </div>
                    </form>
                </section>
            </main>
        </template>
    </div>
</x-layouts.app>
