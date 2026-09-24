<?php

namespace App\Jobs;

use App\Models\TechnicianProfile;
use App\Services\FieldService\FieldSyncService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\WithoutOverlapping;

class ProcessFieldSyncJob implements ShouldQueue
{
    use Queueable;

    /**
     * @param  array<int, array<string, mixed>>  $operations
     */
    public function __construct(public TechnicianProfile $technician, public array $operations)
    {
        $this->onQueue('field-sync');
    }

    /**
     * Operations of one technician are applied strictly in order, so a
     * later batch cannot overtake an earlier one (e.g. "vor Ort" before "unterwegs").
     *
     * @return array<int, object>
     */
    public function middleware(): array
    {
        return [(new WithoutOverlapping("field-sync-{$this->technician->id}"))->releaseAfter(5)];
    }

    public function handle(FieldSyncService $sync): void
    {
        $sync->apply($this->technician, $this->operations);
    }
}
