<nav class="p-3 space-y-1" aria-label="Hauptnavigation">
    <x-nav-link :href="route('agent.tickets.index')" :active="request()->routeIs('agent.tickets.*')">Tickets</x-nav-link>
    @module('team-chat')
        @can('chat.channels.view')
            <x-nav-link :href="route('agent.chat.index')" :active="request()->routeIs('agent.chat.*')"
                        :badge="app(\App\Services\Chat\ChatService::class)->totalUnread(auth()->user()) ?: null">Team-Chat</x-nav-link>
        @endcan
    @endmodule
    @module('change-management')
        @can('changes.approve')
            <x-nav-link :href="route('agent.approvals')" :active="request()->routeIs('agent.approvals')"
                        :badge="\App\Models\CabApproval::query()->where('approver_user_id', auth()->id())->where('decision', 'pending')->count() ?: null">CAB-Freigaben</x-nav-link>
        @endcan
    @endmodule
    @module('field-service')
        @can('dispatch.manage')
            <x-nav-link :href="route('agent.dispatch')" :active="request()->routeIs('agent.dispatch')">Einsatzplanung</x-nav-link>
        @endcan
    @endmodule
    @module('field-service')
        @if (auth()->user()->can('appointments.view.own') && auth()->user()->technicianProfile?->active)
            <x-nav-link :href="route('field.app')">Techniker-App</x-nav-link>
        @endif
    @endmodule
    @module('knowledge-base')
        @can('kb.articles.view')
            <x-nav-link :href="route('agent.kb.index')" :active="request()->routeIs('agent.kb.*')">Wissensdatenbank</x-nav-link>
        @endcan
    @endmodule

    @foreach (auth()->user()->teams as $navTeam)
        <p class="px-3 pt-4 pb-1 text-[11px] font-semibold uppercase tracking-wide text-slate-400">{{ $navTeam->name }}</p>
        @module('reporting')
            <x-nav-link :href="route('agent.team.dashboard', $navTeam)" :active="request()->is('agent/team/'.$navTeam->id.'/dashboard')">Dashboard</x-nav-link>
        @endmodule
        @if ($navTeam->pivot->role_in_team === 'team_admin')
            <x-nav-link :href="route('agent.team.settings', $navTeam)" :active="request()->is('agent/team/'.$navTeam->id.'/settings*')">Einstellungen</x-nav-link>
        @endif
    @endforeach
</nav>
