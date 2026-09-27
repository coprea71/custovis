<div class="flex flex-wrap items-center justify-between gap-4">
    <div>
        <h1 class="text-xl font-semibold text-slatecalm-900">{{ $title }}</h1>
        <p class="text-sm text-slate-500">{{ $readOnly ? 'Nur-Lese-Ansicht. Rechnungen erstellen nur Team-Admins.' : 'Leistungen des Teams abrechnen. Versand als E-Rechnung (ZUGFeRD-PDF und XRechnung-XML).' }}</p>
    </div>
    <div class="flex gap-2 text-sm">
        <a href="{{ route('agent.team.invoices', $team) }}" class="px-4 py-2 rounded-xl bg-slatecalm-100 text-slate-700 font-medium">Übersicht</a>
        @can('invoices.manage')
            <a href="{{ route('admin.invoices.settings') }}" class="px-4 py-2 rounded-xl bg-slatecalm-100 text-slate-700 font-medium">Einstellungen</a>
        @endcan
    </div>
</div>
