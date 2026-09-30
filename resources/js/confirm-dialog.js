// Replaces the browser's confirm() behind every wire:confirm with the in-app
// dialog from layouts/app.blade.php. Our directive.init hook runs after
// Livewire's own wire:confirm handler, so it overwrites the callback that
// wire:click consults before running the action.

document.addEventListener('alpine:init', () => {
    Alpine.data('confirmDialog', () => ({
        open: false,
        message: '',
        onConfirm: null,

        show(detail) {
            this.message = detail.message;
            this.onConfirm = detail.onConfirm;
            this.open = true;
            this.$nextTick(() => this.$refs.confirm.focus());
        },

        confirm() {
            this.open = false;
            this.onConfirm?.();
        },

        cancel() {
            this.open = false;
            this.onConfirm = null;
        },
    }));
});

document.addEventListener('livewire:init', () => {
    Livewire.hook('directive.init', ({ el, directive }) => {
        if (directive.value !== 'confirm' || directive.modifiers.includes('prompt')) return;

        const message = directive.expression.replaceAll('\\n', '\n') || 'Wirklich fortfahren?';

        // wire:click only proceeds synchronously, so we always stop it here
        // and call the action ourselves once the user has confirmed.
        el.__livewire_confirm = (action, instead) => {
            instead();
            window.dispatchEvent(new CustomEvent('custovis-confirm', { detail: { message, onConfirm: action } }));
        };
    });
});
