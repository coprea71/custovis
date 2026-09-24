<?php

use App\Livewire\Admin\ChecklistTemplateManager;
use App\Livewire\Admin\CmdbManager;
use App\Livewire\Admin\ComplianceCenter;
use App\Livewire\Admin\CustomerManager;
use App\Livewire\Admin\KnowledgeBase\ArticleEditor;
use App\Livewire\Admin\KnowledgeBase\ArticleIndex;
use App\Livewire\Admin\KnowledgeBase\CategoryManager;
use App\Livewire\Admin\MailboxManager;
use App\Livewire\Admin\ManagementDashboard;
use App\Livewire\Admin\ModuleManager;
use App\Livewire\Admin\RoleManager;
use App\Livewire\Admin\ServiceCatalogManager;
use App\Livewire\Admin\Settings\ThemeManager;
use App\Livewire\Admin\SlaManager;
use App\Livewire\Admin\SystemMaintenance;
use App\Livewire\Admin\TeamManager;
use App\Livewire\Admin\TechnicianManager;
use App\Livewire\Admin\UserManager;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:web'])->group(function () {
    Route::get('/dashboard', ManagementDashboard::class)->middleware('module:reporting')->name('dashboard');
    Route::get('/users', UserManager::class)->name('users');
    Route::get('/teams', TeamManager::class)->name('teams');
    Route::get('/roles', RoleManager::class)->name('roles');
    Route::get('/sla', SlaManager::class)->name('sla');
    Route::get('/cmdb', CmdbManager::class)->middleware('module:cmdb')->name('cmdb');
    Route::get('/mailboxes', MailboxManager::class)->name('mailboxes.index');
    Route::get('/service-catalog', ServiceCatalogManager::class)->middleware('module:service-catalog')->name('service-catalog.index');
    Route::get('/knowledge-base', ArticleIndex::class)->middleware('module:knowledge-base')->name('kb.articles.index');
    Route::get('/knowledge-base/categories', CategoryManager::class)->middleware('module:knowledge-base')->name('kb.categories');
    Route::get('/knowledge-base/articles/create', ArticleEditor::class)->middleware('module:knowledge-base')->name('kb.articles.create');
    Route::get('/knowledge-base/articles/{article}', ArticleEditor::class)->middleware('module:knowledge-base')->name('kb.articles.edit');
    Route::get('/technicians', TechnicianManager::class)->middleware('module:field-service')->name('technicians');
    Route::get('/checklists', ChecklistTemplateManager::class)->middleware('module:field-service')->name('checklists');
    Route::get('/customers', CustomerManager::class)->name('customers');
    Route::get('/compliance', ComplianceCenter::class)->name('compliance');
    Route::get('/system/migrate', SystemMaintenance::class)->name('system.migrate');
    Route::get('/modules', ModuleManager::class)->name('modules');
    Route::get('/settings/theme', ThemeManager::class)->name('settings.theme');
});
