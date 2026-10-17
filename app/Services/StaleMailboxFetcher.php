<?php

namespace App\Services;

use App\Jobs\FetchMailboxJob;
use App\Jobs\SendPendingMailsJob;
use App\Models\Mailbox;
use App\Models\Team;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;

/**
 * Fallback for hosts whose cron runs rarely: opening the ticket list fetches
 * mailboxes that were not polled for a while, at most once per interval,
 * and sends the resulting automatic mails right away.
 */
class StaleMailboxFetcher
{
    public const INTERVAL_MINUTES = 5;

    /**
     * @param  Builder<Team>  $teams
     */
    public function fetchStale(Builder $teams): void
    {
        $dispatched = false;

        Mailbox::query()
            ->where('active', true)
            ->whereIn('team_id', $teams->select('teams.id'))
            ->where(fn (Builder $query) => $query
                ->whereNull('last_fetched_at')
                ->orWhere('last_fetched_at', '<', now()->subMinutes(self::INTERVAL_MINUTES)))
            ->each(function (Mailbox $mailbox) use (&$dispatched) {
                // Also throttles failing mailboxes, whose last_fetched_at never advances.
                if (Cache::add("mailbox-fetch-on-view:{$mailbox->id}", true, now()->addMinutes(self::INTERVAL_MINUTES))) {
                    FetchMailboxJob::dispatchAfterResponse($mailbox);
                    $dispatched = true;
                }
            });

        // Runs after the fetch jobs (same after-response order), so their
        // acknowledgement mails go out now instead of waiting for the cron.
        if ($dispatched) {
            SendPendingMailsJob::dispatchAfterResponse();
        }
    }
}
