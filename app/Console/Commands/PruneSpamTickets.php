<?php

namespace App\Console\Commands;

use App\Services\SpamFilterService;
use Illuminate\Console\Command;

class PruneSpamTickets extends Command
{
    protected $signature = 'spam:prune';

    protected $description = 'Delete spam tickets older than the spam retention period';

    public function handle(SpamFilterService $spamFilter): int
    {
        $deleted = $spamFilter->pruneExpired();

        $this->info("{$deleted} spam ticket(s) deleted.");

        return self::SUCCESS;
    }
}
