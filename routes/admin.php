<?php

use App\Livewire\Admin\MailboxManager;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:web'])->group(function () {
    Route::get('/mailboxes', MailboxManager::class)->name('mailboxes.index');
});
