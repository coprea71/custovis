<div class="flex-1 overflow-y-auto p-6 space-y-4 max-w-3xl">
    <h1 class="text-xl font-semibold text-slatecalm-900">Einstellungen — {{ $team->name }}</h1>
    @if ($isTeamAdmin)
        <label class="flex items-start gap-3 bg-white border border-slatecalm-200 rounded-2xl p-5 cursor-pointer">
            <input type="checkbox" wire:click="toggleAutoAssign" @checked($team->auto_assign_on_view) class="mt-1 rounded border-slatecalm-300 text-calm-600">
            <span>
                <span class="block font-medium text-slatecalm-900">Tickets beim Öffnen automatisch zuweisen</span>
                <span class="block text-sm text-slate-500">Öffnet ein Teammitglied ein offenes, noch niemandem zugewiesenes Ticket, wird es ihm als Bearbeiter zugewiesen.</span>
            </span>
        </label>
    @endif
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        @foreach ($pages as $route => [$label, $description])
            <a href="{{ route($route, $team) }}" class="bg-white border border-slatecalm-200 rounded-2xl p-5 hover:border-calm-400">
                <p class="font-medium text-slatecalm-900">{{ $label }}</p>
                <p class="text-sm text-slate-500">{{ $description }}</p>
            </a>
        @endforeach
    </div>
</div>
