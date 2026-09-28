<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Per-agent read position in a ticket: read state is personal, so one agent
 * opening a ticket does not hide new messages from colleagues.
 */
class TicketReadState extends Model
{
    protected $fillable = [
        'user_id',
        'ticket_id',
        'last_read_message_id',
    ];

    public static function watermarkFor(User $user, int $ticketId): int
    {
        return (int) static::query()->where('user_id', $user->id)->where('ticket_id', $ticketId)->value('last_read_message_id');
    }

    public static function markRead(User $user, Ticket $ticket): void
    {
        static::moveWatermark($user, $ticket->id, (int) $ticket->messages()->max('id'));
    }

    /**
     * Moves the watermark just below the newest message of someone else, so
     * exactly that message counts as unread again.
     */
    public static function markUnread(User $user, Ticket $ticket): void
    {
        $latestForeign = (int) $ticket->messages()->notAuthoredBy($user)->max('id');

        if ($latestForeign > 0) {
            static::moveWatermark($user, $ticket->id, $latestForeign - 1);
        }
    }

    public static function moveWatermark(User $user, int $ticketId, int $messageId): void
    {
        static::query()->updateOrCreate(
            ['user_id' => $user->id, 'ticket_id' => $ticketId],
            ['last_read_message_id' => $messageId],
        );
    }
}
