<?php

use App\Jobs\FetchMailboxJob;
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
