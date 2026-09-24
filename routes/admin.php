<?php

use App\Livewire\Admin\MailboxManager;
use App\Livewire\Admin\ManagementDashboard;
use App\Livewire\Admin\ServiceCatalogManager;
use App\Livewire\Admin\Settings\ThemeManager;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:web'])->group(function () {
    Route::get('/dashboard', ManagementDashboard::class)->name('dashboard');
    Route::get('/mailboxes', MailboxManager::class)->name('mailboxes.index');
    Route::get('/service-catalog', ServiceCatalogManager::class)->name('service-catalog.index');
    Route::get('/settings/theme', ThemeManager::class)->name('settings.theme');
});
