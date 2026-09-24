<?php

namespace App\Console\Commands;

use App\Services\Dashboard\DashboardSnapshotService;
use Illuminate\Console\Command;

class RefreshDashboardSnapshots extends Command
{
    protected $signature = 'dashboards:refresh-snapshots';

    protected $description = 'Recompute the management and team dashboard snapshots';

    public function handle(DashboardSnapshotService $snapshots): int
    {
        $count = $snapshots->refreshAll();
        $this->info("{$count} dashboard snapshot(s) refreshed.");

        return self::SUCCESS;
    }
}
