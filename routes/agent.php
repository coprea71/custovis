<?php

use App\Livewire\Agent\Team\ApiKeyManager;
use App\Livewire\Agent\Team\GitIssueConnectionManager;
use App\Livewire\Agent\TicketWorkspace;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:web'])->group(function () {
    Route::get('/', TicketWorkspace::class)->name('tickets.index');
    Route::get('/tickets/{ticket}', TicketWorkspace::class)->name('tickets.show');

    Route::get('/team/{team}/settings/api-keys', ApiKeyManager::class)->name('team.api-keys');
    Route::get('/team/{team}/settings/git-issues', GitIssueConnectionManager::class)->name('team.git-issues');
});
