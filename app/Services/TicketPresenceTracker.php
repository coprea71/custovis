<?php

namespace App\Services;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

/**
 * Polling fallback for collision detection when no Reverb server exists:
 * each open ticket view sends a heartbeat, viewers expire without one.
 */
class TicketPresenceTracker
{
    // Well above the 10s poll interval, so one delayed heartbeat does not hide a colleague.
    private const EXPIRES_AFTER_SECONDS = 30;

    /**
     * Records $user as viewing $ticket and returns the names of the other viewers.
     *
     * @return list<string>
     */
    public function heartbeat(Ticket $ticket, User $user): array
    {
        $key = "ticket-presence.{$ticket->id}";
        $now = now()->timestamp;

        $viewers = collect(Cache::get($key, []))
            ->filter(fn (array $viewer) => $viewer['seen'] > $now - self::EXPIRES_AFTER_SECONDS)
            ->put($user->id, ['name' => $user->name, 'seen' => $now]);

        Cache::put($key, $viewers->all(), self::EXPIRES_AFTER_SECONDS * 2);

        return $viewers->except($user->id)->pluck('name')->values()->all();
    }
}
