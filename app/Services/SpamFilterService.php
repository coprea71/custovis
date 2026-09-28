<?php

namespace App\Services;

use App\Models\SpamRule;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Spam handling for mail tickets: marking a ticket blocks its sender (or
 * domain) for the ticket's team and moves the ticket to the admin spam
 * folder, where it can be released or is deleted after RETENTION_DAYS.
 */
class SpamFilterService
{
    public const RETENTION_DAYS = 30;

    public function markAsSpam(Ticket $ticket, string $type, User $by): SpamRule
    {
        abort_unless(in_array($type, SpamRule::TYPES, true) && $ticket->requester_email, 422);

        return DB::transaction(function () use ($ticket, $type, $by) {
            $rule = SpamRule::query()->firstOrCreate(
                ['team_id' => $ticket->team_id, 'type' => $type, 'value' => SpamRule::valueFor($type, $ticket->requester_email)],
                ['created_by' => $by->id],
            );

            $ticket->update(['spam_at' => now()]);

            return $rule;
        });
    }

    public function release(Ticket $ticket): void
    {
        $ticket->update(['spam_at' => null]);
    }

    /**
     * Messages, attachments rows etc. go with the ticket via FK cascade;
     * only the attachment files need removing by hand.
     */
    public function delete(Ticket $ticket): void
    {
        Storage::disk(AttachmentService::DISK)->deleteDirectory("ticket-attachments/{$ticket->id}");
        $ticket->delete();
    }

    public function pruneExpired(): int
    {
        $deleted = 0;

        Ticket::query()->onlySpam()->where('spam_at', '<', now()->subDays(self::RETENTION_DAYS))
            ->each(function (Ticket $ticket) use (&$deleted) {
                $this->delete($ticket);
                $deleted++;
            });

        return $deleted;
    }
}
