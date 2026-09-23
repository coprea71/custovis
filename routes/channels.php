<?php

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Collision detection: presence channel per ticket, joined while an agent
// has the ticket open in the workspace (see resources/js/collision.js).
Broadcast::channel('ticket.{ticketId}', function (User $user, int $ticketId) {
    if (! Ticket::query()->whereKey($ticketId)->exists()) {
        return false;
    }

    return ['id' => $user->id, 'name' => $user->name];
});
