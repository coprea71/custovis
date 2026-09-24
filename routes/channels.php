<?php

use App\Models\ChatChannel;
use App\Models\ChatDirectThread;
use App\Models\Ticket;
use App\Services\Chat\ChatAccess;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Team chat (9.md): same access rules as the Livewire chat UI.
Broadcast::channel('chat.channel.{channel}', fn (User $user, ChatChannel $channel) => app(ChatAccess::class)->canView($user, $channel));
Broadcast::channel('chat.direct.{thread}', fn (User $user, ChatDirectThread $thread) => app(ChatAccess::class)->canView($user, $thread));

// Collision detection: presence channel per ticket, joined while an agent
// has the ticket open in the workspace (see resources/js/collision.js).
Broadcast::channel('ticket.{ticketId}', function (User $user, int $ticketId) {
    $ticket = Ticket::query()->find($ticketId);

    if (! $ticket || ! $user->can('view', $ticket)) {
        return false;
    }

    return ['id' => $user->id, 'name' => $user->name];
});
