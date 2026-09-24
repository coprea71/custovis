<?php

use App\Livewire\Admin\KnowledgeBase\ArticleEditor;
use App\Livewire\Admin\KnowledgeBase\ArticleIndex;
use App\Livewire\Admin\KnowledgeBase\CategoryManager;
use App\Livewire\Admin\MailboxManager;
use App\Livewire\Admin\ManagementDashboard;
use App\Livewire\Admin\ServiceCatalogManager;
use App\Livewire\Admin\Settings\ThemeManager;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:web'])->group(function () {
    Route::get('/dashboard', ManagementDashboard::class)->name('dashboard');
    Route::get('/mailboxes', MailboxManager::class)->name('mailboxes.index');
    Route::get('/service-catalog', ServiceCatalogManager::class)->name('service-catalog.index');
    Route::get('/knowledge-base', ArticleIndex::class)->name('kb.articles.index');
    Route::get('/knowledge-base/categories', CategoryManager::class)->name('kb.categories');
    Route::get('/knowledge-base/articles/create', ArticleEditor::class)->name('kb.articles.create');
    Route::get('/knowledge-base/articles/{article}', ArticleEditor::class)->name('kb.articles.edit');
    Route::get('/settings/theme', ThemeManager::class)->name('settings.theme');
});
