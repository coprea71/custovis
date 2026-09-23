<?php

use App\Livewire\Agent\TicketWorkspace;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:web'])->group(function () {
    Route::get('/', TicketWorkspace::class)->name('tickets.index');
    Route::get('/tickets/{ticket}', TicketWorkspace::class)->name('tickets.show');
});
