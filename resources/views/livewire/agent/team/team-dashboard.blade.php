<div class="flex-1 overflow-y-auto p-6">
    <x-dashboard.team-section :team="$team" :snapshot="$snapshot" :recent-tickets="$recentTickets"
                              :heading="'Team-Dashboard: '.$team->name" />
</div>
