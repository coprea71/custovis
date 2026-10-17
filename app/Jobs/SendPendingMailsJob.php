<?php

namespace App\Jobs;

use App\Services\WebCronService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Artisan;

/**
 * Works off the "mail-send" queue right after a fetch on view, so the
 * acknowledgement of a just-imported ticket does not wait for the next
 * (possibly rare) cron run. Only ever dispatched after the response.
 */
class SendPendingMailsJob implements ShouldQueue
{
    use Queueable;

    // Stays below common PHP execution limits; the cron picks up the rest.
    private const MAX_SECONDS = 20;

    public function handle(): void
    {
        Artisan::call('queue:work', [
            '--queue' => 'mail-send',
            '--stop-when-empty' => true,
            '--max-time' => self::MAX_SECONDS,
            '--tries' => 3,
            '--memory' => WebCronService::workerMemoryLimitMb(),
        ]);
    }
}
