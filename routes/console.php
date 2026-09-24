<?php

use App\Jobs\FetchMailboxJob;
use App\Jobs\SyncGitIssuesJob;
use App\Models\GitIssueConnection;
use App\Models\Mailbox;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::call(function () {
    Mailbox::query()->where('active', true)->each(
        fn (Mailbox $mailbox) => FetchMailboxJob::dispatch($mailbox)
    );
})->everyMinute()->name('mailboxes:fetch');

Schedule::command('sla:check-breaches')->everyFiveMinutes()->name('sla:check-breaches');

Schedule::call(function () {
    GitIssueConnection::query()
        ->where('sync_mode', GitIssueConnection::SYNC_POLL)
        ->whereNull('revoked_at')
        ->each(fn (GitIssueConnection $connection) => SyncGitIssuesJob::dispatch($connection));
})->everyFiveMinutes()->name('git-issues:sync');

Schedule::command('chat:prune')->daily()->name('chat:prune');

Schedule::command('field:prune-locations')->daily()->name('field:prune-locations');

Schedule::command('dashboards:refresh-snapshots')->hourly()->name('dashboards:refresh-snapshots');
