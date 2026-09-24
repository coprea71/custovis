<?php

use App\Http\Controllers\Portal\PortalAuthController;
use App\Http\Controllers\Portal\PortalPasswordResetController;
use App\Http\Middleware\EnsureCustomerIsActive;
use App\Http\Middleware\ScopeTicketsToCustomer;
use App\Livewire\Portal\KnowledgeBase;
use App\Livewire\Portal\NewRequest;
use App\Livewire\Portal\TicketDetail;
use App\Livewire\Portal\TicketList;
use Illuminate\Support\Facades\Route;

Route::middleware('guest:customer')->group(function () {
    Route::get('/login', [PortalAuthController::class, 'create'])->name('login');
    Route::post('/login', [PortalAuthController::class, 'store'])->name('login.store');
    Route::get('/forgot-password', [PortalPasswordResetController::class, 'create'])->name('password.request');
    Route::post('/forgot-password', [PortalPasswordResetController::class, 'store'])->name('password.email');
    Route::get('/reset-password/{token}', [PortalPasswordResetController::class, 'edit'])->name('password.reset');
    Route::post('/reset-password', [PortalPasswordResetController::class, 'update'])->name('password.update');
});

// ScopeTicketsToCustomer runs before route model binding (priority list in
// bootstrap/app.php), so {ticket} is resolved through CustomerOwnedScope.
Route::middleware(['auth:customer', EnsureCustomerIsActive::class, ScopeTicketsToCustomer::class])->group(function () {
    Route::post('/logout', [PortalAuthController::class, 'destroy'])->name('logout');

    Route::get('/', TicketList::class)->name('tickets.index');
    Route::get('/tickets/{ticket}', TicketDetail::class)->name('tickets.show');
    Route::get('/requests/new', NewRequest::class)->name('requests.create');
    Route::get('/kb', KnowledgeBase::class)->name('kb.index');
    Route::get('/kb/{article}', KnowledgeBase::class)->whereNumber('article')->name('kb.show');
});
