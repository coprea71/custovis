<?php

namespace App\Livewire\Agent;

use App\Models\AuditLog;
use App\Models\CabApproval;
use App\Services\ChangeApprovalService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * CAB approvals waiting for the signed-in user (21.md). Only the named
 * approver can decide; the outcome rule lives in ChangeApprovalService.
 */
#[Layout('layouts.agent')]
class ApprovalInbox extends Component
{
    /** @var array<int, string> */
    public array $comments = [];

    public function mount(): void
    {
        Gate::authorize('changes.approve');
    }

    public function decide(int $approvalId, bool $approved, ChangeApprovalService $service): void
    {
        Gate::authorize('changes.approve');
        $approval = $this->pending()->findOrFail($approvalId);
        $comment = mb_substr(trim($this->comments[$approvalId] ?? ''), 0, 2000) ?: null;

        $service->recordDecision($approval->ticket, Auth::id(), $approved, $comment);
        AuditLog::record($approved ? 'cab.approved' : 'cab.rejected', Auth::user(), $approval->ticket->team, $approval->ticket);
        unset($this->comments[$approvalId]);
    }

    public function render()
    {
        return view('livewire.agent.approval-inbox', [
            'approvals' => $this->pending()->with('ticket.change')->latest()->get(),
        ]);
    }

    private function pending()
    {
        return CabApproval::query()->where('approver_user_id', Auth::id())->where('decision', CabApproval::PENDING);
    }
}
