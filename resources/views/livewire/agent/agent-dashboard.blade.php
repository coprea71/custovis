<div class="flex-1 overflow-y-auto p-6 space-y-10">
    <h1 class="sr-only">Dashboard</h1>
    @foreach ($sections as $section)
        <x-dashboard.team-section :team="$section['team']" :snapshot="$section['snapshot']" :recent-tickets="$section['recentTickets']" />
    @endforeach
</div>
