<?php

use App\Livewire\Admin\MailboxManager;
use App\Livewire\Admin\ServiceCatalogManager;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:web'])->group(function () {
    Route::get('/mailboxes', MailboxManager::class)->name('mailboxes.index');
    Route::get('/service-catalog', ServiceCatalogManager::class)->name('service-catalog.index');
});
