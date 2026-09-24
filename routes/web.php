<?php

use App\Http\Controllers\AccountSecurityController;
use App\Http\Controllers\InstallController;
use App\Http\Controllers\Themes\ThemePreviewController;
use App\Http\Middleware\EnsureNotInstalled;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (auth('web')->check()) {
        return redirect('/agent');
    }

    return auth('customer')->check() ? redirect()->route('portal.tickets.index') : view('welcome');
})->name('home');

// Web installer (12.md): deliberately outside the 'web' middleware group —
// a fresh upload has no APP_KEY yet, so sessions/cookies cannot work.
Route::withoutMiddleware('web')->middleware(EnsureNotInstalled::class)->group(function () {
    Route::get('/install', [InstallController::class, 'show'])->name('install');
    Route::post('/install', [InstallController::class, 'store'])->name('install.store');
});

Route::get('/account/security', AccountSecurityController::class)
    ->middleware('auth:web')
    ->name('account.security');

Route::get('/themes/{theme:slug}/preview', ThemePreviewController::class)
    ->middleware('auth:web')
    ->name('themes.preview');
