<?php

namespace App\Console\Commands;

use App\Models\TechnicianLocation;
use Illuminate\Console\Command;

class PruneTechnicianLocations extends Command
{
    protected $signature = 'field:prune-locations';

    protected $description = 'Delete technician GPS positions older than the configured retention period (DSGVO)';

    public function handle(): int
    {
        $days = max(1, (int) config('custovis.field_service.location_retention_days'));
        $deleted = TechnicianLocation::query()->where('recorded_at', '<', now()->subDays($days))->delete();

        $this->info("{$deleted} location(s) pruned.");

        return self::SUCCESS;
    }
}
