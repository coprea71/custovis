<?php

namespace App\Livewire\Agent;

use App\Models\Ticket;
use App\Services\TicketPresenceTracker;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Collision detection without WebSockets (see App\Support\Realtime).
 */
class TicketPresence extends Component
{
    #[Locked]
    public int $ticketId;

    public function render(TicketPresenceTracker $tracker)
    {
        $ticket = Ticket::query()->findOrFail($this->ticketId);
        Gate::authorize('view', $ticket);

        return view('livewire.agent.ticket-presence', [
            'others' => $tracker->heartbeat($ticket, Auth::user()),
        ]);
    }
}
