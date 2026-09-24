<?php

use App\Http\Controllers\Field\FieldController;
use Illuminate\Support\Facades\Route;

// Technician PWA (14.md). Session-authenticated like the agent area; the
// permission check happens in FieldController / FieldSyncRequest.
Route::middleware(['auth:web'])->group(function () {
    Route::get('/', [FieldController::class, 'show'])->name('app');
    Route::get('/today', [FieldController::class, 'today'])->name('today');
    Route::post('/sync', [FieldController::class, 'sync'])->middleware('throttle:60,1')->name('sync');
});
