<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Ticket;
use App\Models\TicketAttachment;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

/**
 * Merges duplicate tickets into one target: messages (with their Message-IDs,
 * so customer replies to old mails thread into the target), attachments and
 * time entries move over; the sources stay as closed stubs pointing to the
 * target, so their ticket numbers keep resolving.
 */
class TicketMergeService
{
    /**
     * @param  Collection<int, Ticket>  $sources
     */
    public function merge(Ticket $target, Collection $sources, User $by): Ticket
    {
        $this->guard($target, $sources);
        $fileMoves = [];

        DB::transaction(function () use ($target, $sources, $by, &$fileMoves) {
            foreach ($sources as $source) {
                $fileMoves = [...$fileMoves, ...$this->moveContent($source, $target)];
                $this->closeAsMerged($source, $target, $by);
            }

            $target->recordInternalNote('Tickets '.$sources->map(fn (Ticket $ticket) => '#'.$ticket->id)->implode(', ')
                .' am '.now()->format('d.m.Y H:i').' durch '.$by->name.' hierher zusammengeführt.', $by->id);
            AuditLog::record('ticket.merged', $by, $target->team, $target, ['source_ids' => $sources->pluck('id')->all()]);
        });

        // After the commit: a rolled back merge must not leave files in the target's folder.
        $disk = Storage::disk(AttachmentService::DISK);
        foreach ($fileMoves as [$from, $to]) {
            if ($disk->exists($from)) {
                $disk->move($from, $to);
            }
        }

        return $target->refresh();
    }

    /**
     * @param  Collection<int, Ticket>  $sources
     */
    private function guard(Ticket $target, Collection $sources): void
    {
        $invalid = $sources->isEmpty()
            || $sources->contains(fn (Ticket $ticket) => $ticket->is($target) || $ticket->team_id !== $target->team_id || $ticket->merged_into_id !== null)
            || $target->merged_into_id !== null;

        if ($invalid) {
            throw ValidationException::withMessages(['merge' => 'Nur noch nicht zusammengeführte Tickets desselben Teams lassen sich zusammenführen.']);
        }
    }

    /**
     * @return list<array{string, string}> attachment files to move: [from, to]
     */
    private function moveContent(Ticket $source, Ticket $target): array
    {
        $moves = [];
        $attachments = TicketAttachment::query()
            ->whereIn('ticket_message_id', $source->messages()->select('id'))
            ->where('disk', AttachmentService::DISK)->get();

        // Retention and spam deletion remove a ticket's folder, so the files follow their messages.
        foreach ($attachments as $attachment) {
            $newPath = "ticket-attachments/{$target->id}/".basename($attachment->path);
            $moves[] = [$attachment->path, $newPath];
            $attachment->update(['path' => $newPath]);
        }

        $source->messages()->update(['ticket_id' => $target->id]);
        $source->timeEntries()->update(['ticket_id' => $target->id]);
        $target->update(['tags' => array_values(array_unique([...($target->tags ?? []), ...($source->tags ?? [])])) ?: null]);
        $target->raisePriority($source->priority);

        return $moves;
    }

    private function closeAsMerged(Ticket $source, Ticket $target, User $by): void
    {
        // Muted first: closing would otherwise send the customer a "resolved" mail.
        $source->update(['auto_mails_muted' => true]);
        $source->update(['status' => 'closed', 'closed_at' => $source->closed_at ?? now(), 'merged_into_id' => $target->id]);
        $source->recordInternalNote('Am '.now()->format('d.m.Y H:i').' durch '.$by->name.' in Ticket #'.$target->id.' zusammengeführt.', $by->id);
    }
}
