@props(['area' => 'agent'])
@php($user = auth()->user())
@php($adminRoute = \App\Support\AdminNavigation::firstRoute($user))
<div {{ $attributes->merge(['class' => 'flex flex-wrap items-center gap-x-3 gap-y-1 text-xs']) }}>
    <a href="{{ route('account.security') }}" class="text-slate-500 hover:text-calm-700">Kontosicherheit</a>
    @if ($area === 'agent' && $adminRoute)
        <a href="{{ route($adminRoute) }}" class="text-slate-500 hover:text-calm-700">Administration</a>
    @elseif ($area === 'admin')
        <a href="{{ route('agent.tickets.index') }}" class="text-slate-500 hover:text-calm-700">Agenten-Bereich</a>
    @endif
    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit" class="text-slate-500 hover:text-red-600">Abmelden</button>
    </form>
</div>
