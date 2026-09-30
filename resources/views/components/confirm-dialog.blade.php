{{-- Global replacement for the browser confirm() behind wire:confirm, see resources/js/confirm-dialog.js. --}}
<div x-data="confirmDialog" x-cloak @custovis-confirm.window="show($event.detail)" @keydown.escape.window="cancel()">
    <div x-show="open" x-transition.opacity class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 p-4" @click.self="cancel()">
        <div class="w-full max-w-md bg-white rounded-2xl shadow-xl p-6 space-y-4" role="alertdialog" aria-modal="true" aria-labelledby="confirm-dialog-title" aria-describedby="confirm-dialog-message">
            <h2 id="confirm-dialog-title" class="font-semibold text-slatecalm-900">Bitte bestätigen</h2>
            <p id="confirm-dialog-message" class="text-sm text-slate-600 whitespace-pre-line" x-text="message"></p>
            <div class="flex justify-end gap-2">
                <button type="button" @click="cancel()" class="text-sm px-4 py-2 rounded-xl bg-slatecalm-100 hover:bg-slatecalm-200 text-slate-700">Abbrechen</button>
                <button type="button" x-ref="confirm" @click="confirm()" class="text-sm px-4 py-2 rounded-xl bg-calm-600 hover:bg-calm-700 text-white font-medium">Bestätigen</button>
            </div>
        </div>
    </div>
</div>
