<?php

namespace App\Livewire\Admin;

use App\Models\Mailbox;
use App\Services\Dashboard\DashboardSnapshotService;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.admin')]
class ManagementDashboard extends Component
{
    public function mount(): void
    {
        Gate::authorize('dashboard.management.view');
    }

    public function render(DashboardSnapshotService $snapshots)
    {
        return view('livewire.admin.management-dashboard', [
            'snapshot' => $snapshots->forScope(null),
            'failingMailboxCount' => Gate::allows('mailboxes.manage') ? Mailbox::query()->withFetchError()->count() : 0,
        ]);
    }
}
