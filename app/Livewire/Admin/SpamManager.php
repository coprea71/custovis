<?php

namespace App\Livewire\Admin;

use App\Models\SpamRule;
use App\Models\Ticket;
use App\Services\SpamFilterService;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.admin')]
class SpamManager extends Component
{
    use WithPagination;

    public ?int $previewId = null;

    public function mount(): void
    {
        Gate::authorize('spam.manage');
    }

    public function togglePreview(int $ticketId): void
    {
        $this->previewId = $this->previewId === $ticketId ? null : $ticketId;
    }

    public function release(int $ticketId, SpamFilterService $spamFilter): void
    {
        Gate::authorize('spam.manage');
        $spamFilter->release(Ticket::query()->onlySpam()->findOrFail($ticketId));
    }

    public function delete(int $ticketId, SpamFilterService $spamFilter): void
    {
        Gate::authorize('spam.manage');
        $spamFilter->delete(Ticket::query()->onlySpam()->findOrFail($ticketId));
    }

    public function deleteRule(int $ruleId): void
    {
        Gate::authorize('spam.manage');
        SpamRule::query()->findOrFail($ruleId)->delete();
    }

    public function render()
    {
        return view('livewire.admin.spam-manager', [
            'tickets' => Ticket::query()->onlySpam()->with('team', 'messages')->latest('spam_at')->paginate(25),
            'rules' => SpamRule::query()->with('team', 'creator')->orderBy('value')->get(),
            'retentionDays' => SpamFilterService::RETENTION_DAYS,
        ]);
    }
}
