// Short "saved / not saved" notice after every persisting Livewire action, so
// users get feedback even when the inline status text is out of view. Hooked
// in centrally instead of in each of the ~40 save/create/add methods.

const PERSISTING_ACTION = /^(save|create|add)/;
// Runs on every filter change in the ticket list, not on a user's save click.
const IGNORED_ACTIONS = ['saveFilters'];

document.addEventListener('alpine:init', () => {
    Alpine.data('saveToast', () => ({
        open: false,
        success: true,
        message: '',
        timer: null,

        show(detail) {
            this.success = detail.success;
            this.message = detail.message;
            this.open = true;
            clearTimeout(this.timer);
            this.timer = setTimeout(() => (this.open = false), detail.success ? 2500 : 5000);
        },
    }));
});

function toast(success, message) {
    window.dispatchEvent(new CustomEvent('custovis-toast', { detail: { success, message } }));
}

function errorMessage(status) {
    if (status === 422) return 'Nicht gespeichert – bitte Eingaben prüfen.';
    if (status === 403) return 'Nicht gespeichert – keine Berechtigung.';

    return 'Fehler beim Speichern – bitte erneut versuchen.';
}

document.addEventListener('livewire:init', () => {
    Livewire.interceptAction(({ action }) => {
        if (!PERSISTING_ACTION.test(action.name) || IGNORED_ACTIONS.includes(action.name)) return;

        action.promise.then(
            () => {
                // Some actions report problems via addError() without throwing.
                const errors = action.component.snapshot?.memo?.errors ?? {};
                Object.keys(errors).length ? toast(false, errorMessage(422)) : toast(true, 'Daten gespeichert');
            },
            (error) => {
                // A cancelled action (status null) was superseded, nothing to report.
                if (error?.status) toast(false, errorMessage(error.status));
            },
        );
    });
});
