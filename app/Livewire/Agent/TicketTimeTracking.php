<?php

namespace App\Livewire\Agent;

use App\Models\Ticket;
use App\Models\TimeEntry;
use App\Models\User;
use App\Services\ModuleAccess;
use App\Support\Duration;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Time accounting per ticket (31.md): manual entries and a start/stop timer.
 */
class TicketTimeTracking extends Component
{
    #[Locked]
    public int $ticketId;

    public string $duration = '';

    public string $workDate = '';

    public string $description = '';

    public bool $billable = true;

    public static function availableFor(?User $user): bool
    {
        return $user !== null && $user->can('time.track') && app(ModuleAccess::class)->allows($user, 'time-tracking');
    }

    public function mount(): void
    {
        abort_unless(self::availableFor(Auth::user()), 403);
        $this->ticket();
        $this->workDate = today()->toDateString();
    }

    public function save(): void
    {
        $this->authorizeAccess();
        $this->validate([
            'duration' => ['required', 'regex:'.Duration::PATTERN],
            'workDate' => ['required', 'date', 'before_or_equal:today'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        $minutes = Duration::parse($this->duration);
        if ($minutes < 1) {
            $this->addError('duration', 'Die Dauer muss mindestens eine Minute betragen.');

            return;
        }

        $this->ticket()->timeEntries()->create([
            'user_id' => Auth::id(),
            'work_date' => $this->workDate,
            'minutes' => $minutes,
            'description' => trim($this->description) ?: null,
            'billable' => $this->billable,
        ]);

        $this->reset('duration', 'description');
        $this->billable = true;
    }

    public function startTimer(): void
    {
        $this->authorizeAccess();
        $this->stopRunningTimer();

        $this->ticket()->timeEntries()->create([
            'user_id' => Auth::id(),
            'work_date' => today(),
            'started_at' => now(),
            'description' => trim($this->description) ?: null,
            'billable' => $this->billable,
        ]);
        $this->reset('description');
    }

    public function stopTimer(): void
    {
        $this->authorizeAccess();
        $this->stopRunningTimer();
    }

    public function delete(int $entryId): void
    {
        $this->authorizeAccess();
        $entry = $this->ticket()->timeEntries()->where('user_id', Auth::id())->findOrFail($entryId);
        abort_if($entry->isLocked(), 403);

        $entry->delete();
    }

    public function render()
    {
        $entries = $this->ticket()->timeEntries()->with('user:id,name')->latest('work_date')->latest('id')->get();

        return view('livewire.agent.ticket-time-tracking', [
            'entries' => $entries,
            'billableMinutes' => $entries->where('billable', true)->sum('minutes'),
            'otherMinutes' => $entries->where('billable', false)->sum('minutes'),
            'running' => $entries->first(fn (TimeEntry $entry) => $entry->isRunning() && $entry->user_id === Auth::id()),
        ]);
    }

    /**
     * One running timer per user: starting a new one (on any ticket) books the old one.
     */
    private function stopRunningTimer(): void
    {
        TimeEntry::query()->where('user_id', Auth::id())->whereNull('minutes')->get()
            ->each(fn (TimeEntry $entry) => $entry->update([
                'minutes' => max(1, (int) ceil($entry->started_at->diffInSeconds(now()) / 60)),
            ]));
    }

    private function authorizeAccess(): void
    {
        abort_unless(self::availableFor(Auth::user()), 403);
    }

    private function ticket(): Ticket
    {
        // Scoped so a tampered ticketId can never reach another team's ticket.
        return Ticket::query()->visibleTo(Auth::user())->findOrFail($this->ticketId);
    }
}
