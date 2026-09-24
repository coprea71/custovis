<?php

namespace App\Services\Chat;

use App\Models\ChatChannel;
use App\Models\ChatDirectThread;
use App\Models\Team;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Single place deciding who may read/write which conversation. Evaluated on
 * every access (Livewire action, broadcast auth, attachment download).
 */
class ChatAccess
{
    public function canView(User $user, ChatChannel|ChatDirectThread $conversation): bool
    {
        if (! $user->can('chat.channels.view')) {
            return false;
        }

        if ($conversation instanceof ChatDirectThread) {
            return $conversation->participants()->whereKey($user->id)->exists();
        }

        return match ($conversation->type) {
            ChatChannel::TYPE_GLOBAL => true,
            ChatChannel::TYPE_TEAM => $this->isTeamMember($user, $conversation->team_id),
            ChatChannel::TYPE_TICKET => $this->canSeeTicket($user, $conversation->ticket),
            default => false,
        };
    }

    public function canPost(User $user, ChatChannel|ChatDirectThread $conversation): bool
    {
        if (! $this->canView($user, $conversation)) {
            return false;
        }

        return ! ($conversation instanceof ChatChannel && $conversation->type === ChatChannel::TYPE_GLOBAL)
            || $user->can('chat.global.post');
    }

    public function canSeeTicket(User $user, ?Ticket $ticket): bool
    {
        return $ticket !== null
            && ((int) $ticket->assigned_to === $user->id || $this->isTeamMember($user, $ticket->team_id));
    }

    /**
     * Global channel, one channel per own team, and ticket chats of own
     * teams that already have messages — channels are created on demand.
     *
     * @return Collection<int, ChatChannel>
     */
    public function visibleChannels(User $user): Collection
    {
        if (! $user->can('chat.channels.view')) {
            return collect();
        }

        $teamIds = $user->teams()->pluck('teams.id');
        $teamChannels = Team::query()->whereKey($teamIds)->get()->map(fn (Team $team) => $this->teamChannel($team));
        $ticketChannels = ChatChannel::query()
            ->where('type', ChatChannel::TYPE_TICKET)
            ->whereHas('ticket', fn ($query) => $query->where(fn ($inner) => $inner->whereIn('team_id', $teamIds)->orWhere('assigned_to', $user->id)))
            ->whereHas('messages')
            ->get();

        return collect([$this->globalChannel()])->merge($teamChannels)->merge($ticketChannels);
    }

    /**
     * @return Collection<int, ChatDirectThread>
     */
    public function directThreads(User $user): Collection
    {
        if (! $user->can('chat.channels.view')) {
            return collect();
        }

        return ChatDirectThread::query()
            ->whereHas('participants', fn ($query) => $query->whereKey($user->id))
            ->with('participants')
            ->get();
    }

    public function globalChannel(): ChatChannel
    {
        return ChatChannel::query()->firstOrCreate(['type' => ChatChannel::TYPE_GLOBAL], ['name' => 'Firmen-Chat']);
    }

    public function teamChannel(Team $team): ChatChannel
    {
        return ChatChannel::query()->firstOrCreate(
            ['type' => ChatChannel::TYPE_TEAM, 'team_id' => $team->id],
            ['name' => $team->name]
        );
    }

    public function ticketChannel(Ticket $ticket): ChatChannel
    {
        return ChatChannel::query()->firstOrCreate(
            ['type' => ChatChannel::TYPE_TICKET, 'ticket_id' => $ticket->id],
            ['name' => "Ticket #{$ticket->id}"]
        );
    }

    public function directThread(User $me, User $other): ChatDirectThread
    {
        abort_if($me->is($other) || ! $me->can('chat.direct.create') || ! $other->can('chat.channels.view'), 403);

        $thread = ChatDirectThread::query()->firstOrCreate(['participant_key' => ChatDirectThread::keyFor($me, $other)]);
        $thread->participants()->syncWithoutDetaching([$me->id, $other->id]);

        return $thread;
    }

    private function isTeamMember(User $user, ?int $teamId): bool
    {
        return $teamId !== null && $user->teams()->whereKey($teamId)->exists();
    }
}
