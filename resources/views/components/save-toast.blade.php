{{-- Save feedback, see resources/js/save-toast.js. Never takes focus, so typing continues undisturbed. --}}
<div x-data="saveToast" x-cloak @custovis-toast.window="show($event.detail)"
     class="fixed top-4 left-1/2 -translate-x-1/2 z-50 pointer-events-none px-4 w-full max-w-sm">
    <div x-show="open" x-transition.opacity.duration.200ms role="status" aria-live="polite"
         :class="success ? 'bg-calm-600 text-white' : 'bg-red-600 text-white'"
         class="rounded-xl shadow-lg px-4 py-2.5 text-sm font-medium text-center">
        <span x-text="(success ? '✓ ' : '✕ ') + message"></span>
    </div>
</div>
