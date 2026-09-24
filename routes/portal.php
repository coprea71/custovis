<?php

use App\Http\Controllers\Portal\PortalAuthController;
use App\Http\Middleware\ScopeTicketsToCustomer;
use App\Livewire\Portal\KnowledgeBase;
use App\Livewire\Portal\NewRequest;
use App\Livewire\Portal\TicketDetail;
use App\Livewire\Portal\TicketList;
use Illuminate\Support\Facades\Route;

Route::middleware('guest:customer')->group(function () {
    Route::get('/login', [PortalAuthController::class, 'create'])->name('login');
    Route::post('/login', [PortalAuthController::class, 'store'])->name('login.store');
});

// ScopeTicketsToCustomer runs before route model binding (priority list in
// bootstrap/app.php), so {ticket} is resolved through CustomerOwnedScope.
Route::middleware(['auth:customer', ScopeTicketsToCustomer::class])->group(function () {
    Route::post('/logout', [PortalAuthController::class, 'destroy'])->name('logout');

    Route::get('/', TicketList::class)->name('tickets.index');
    Route::get('/tickets/{ticket}', TicketDetail::class)->name('tickets.show');
    Route::get('/requests/new', NewRequest::class)->name('requests.create');
    Route::get('/kb', KnowledgeBase::class)->name('kb.index');
    Route::get('/kb/{article}', KnowledgeBase::class)->whereNumber('article')->name('kb.show');
});
