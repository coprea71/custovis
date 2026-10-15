<?php

namespace App\Services;

use App\Jobs\FetchMailboxJob;
use App\Models\Mailbox;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;

/**
 * Fallback for hosts whose cron runs rarely: opening the ticket list fetches
 * mailboxes that were not polled for a while, at most once per interval.
 */
class StaleMailboxFetcher
{
    public const INTERVAL_MINUTES = 5;

    /**
     * @param  Builder<\App\Models\Team>  $teams
     */
    public function fetchStale(Builder $teams): void
    {
        Mailbox::query()
            ->where('active', true)
            ->whereIn('team_id', $teams->select('teams.id'))
            ->where(fn (Builder $query) => $query
                ->whereNull('last_fetched_at')
                ->orWhere('last_fetched_at', '<', now()->subMinutes(self::INTERVAL_MINUTES)))
            ->each(function (Mailbox $mailbox) {
                // Also throttles failing mailboxes, whose last_fetched_at never advances.
                if (Cache::add("mailbox-fetch-on-view:{$mailbox->id}", true, now()->addMinutes(self::INTERVAL_MINUTES))) {
                    FetchMailboxJob::dispatchAfterResponse($mailbox);
                }
            });
    }
}
