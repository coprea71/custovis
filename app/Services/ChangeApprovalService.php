<?php

namespace App\Services;

use App\Models\CabApproval;
use App\Models\Ticket;
use App\States\Change\Approved;
use App\States\Change\CabReview;
use App\States\Change\Rejected;
use Illuminate\Support\Collection;

/**
 * CAB approval workflow (5.md). Approval rule: unanimity — every CAB
 * member must approve for the change to pass; a single rejection rejects
 * it immediately. Chosen over a quorum because change approval is a
 * safety gate (ITIL CAB practice favours consensus over majority here),
 * and the plan left the exact rule open for this session to decide.
 */
class ChangeApprovalService
{
    /**
     * @param  Collection<int, int>  $approverUserIds
     */
    public function requestApprovals(Ticket $ticket, Collection $approverUserIds): void
    {
        $change = $ticket->change;
        $change->state->transitionTo(CabReview::class);

        foreach ($approverUserIds as $userId) {
            CabApproval::query()->updateOrCreate(
                ['ticket_id' => $ticket->id, 'approver_user_id' => $userId],
                ['decision' => CabApproval::PENDING, 'decided_at' => null]
            );
        }
    }

    public function recordDecision(Ticket $ticket, int $approverUserId, bool $approved): void
    {
        $approval = CabApproval::query()
            ->where('ticket_id', $ticket->id)
            ->where('approver_user_id', $approverUserId)
            ->firstOrFail();

        $approval->update([
            'decision' => $approved ? CabApproval::APPROVED : CabApproval::REJECTED,
            'decided_at' => now(),
        ]);

        $this->evaluateOutcome($ticket);
    }

    private function evaluateOutcome(Ticket $ticket): void
    {
        $approvals = CabApproval::query()->where('ticket_id', $ticket->id)->get();
        $change = $ticket->change;

        if ($approvals->contains('decision', CabApproval::REJECTED)) {
            $change->state->transitionTo(Rejected::class);

            return;
        }

        if ($approvals->every(fn (CabApproval $a) => $a->decision === CabApproval::APPROVED)) {
            $change->state->transitionTo(Approved::class);
        }
    }
}
