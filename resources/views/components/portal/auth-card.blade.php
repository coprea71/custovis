@props(['title', 'subtitle'])

<x-layouts.app :title="'Kundenportal — '.$title" body-class="flex items-center justify-center px-4" :scripts="false">
    <div class="w-full max-w-sm bg-white border border-slatecalm-200 rounded-2xl p-8 shadow-sm">
        <div class="w-10 h-10 rounded-xl bg-calm-600 flex items-center justify-center text-white font-bold mb-4">C</div>
        <h1 class="text-lg font-semibold text-slatecalm-900">Kundenportal</h1>
        <p class="text-sm text-slate-500 mb-6">{{ $subtitle }}</p>

        @if (session('status'))
            <div class="mb-4 text-sm text-calm-800 bg-calm-50 rounded-xl px-3 py-2" role="status">{{ session('status') }}</div>
        @endif

        @if ($errors->any())
            <div class="mb-4 text-sm text-red-600" role="alert">{{ $errors->first() }}</div>
        @endif

        {{ $slot }}
    </div>
</x-layouts.app>
