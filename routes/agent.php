<?php

use App\Http\Controllers\Chat\ChatAttachmentController;
use App\Livewire\Agent\ApprovalInbox;
use App\Livewire\Agent\Chat\ChatWorkspace;
use App\Livewire\Agent\CreateTicket;
use App\Livewire\Agent\DispatchBoard;
use App\Livewire\Agent\HelpCenter;
use App\Livewire\Agent\KnowledgeBase\ArticleBrowser;
use App\Livewire\Agent\Team\AiSettingsManager;
use App\Livewire\Agent\Team\ApiKeyManager;
use App\Livewire\Agent\Team\CannedResponseManager;
use App\Livewire\Agent\Team\ErpConnectionManager;
use App\Livewire\Agent\Team\GitIssueConnectionManager;
use App\Livewire\Agent\Team\TeamDashboard;
use App\Livewire\Agent\Team\TeamSettings;
use App\Livewire\Agent\Team\WhatsappAccountManager;
use App\Livewire\Agent\TicketWorkspace;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:web'])->group(function () {
    Route::get('/', TicketWorkspace::class)->name('tickets.index');
    Route::get('/tickets/create', CreateTicket::class)->name('tickets.create');
    Route::get('/tickets/{ticket}', TicketWorkspace::class)->name('tickets.show');

    Route::get('/help', HelpCenter::class)->name('help.index');
    Route::get('/help/{topic}', HelpCenter::class)->where('topic', '[a-z0-9-]+')->name('help.show');

    Route::get('/chat', ChatWorkspace::class)->middleware('module:team-chat')->name('chat.index');
    Route::get('/chat/ticket/{ticket}', ChatWorkspace::class)->middleware('module:team-chat')->name('chat.ticket');
    Route::get('/chat/attachments/{message}', ChatAttachmentController::class)->middleware('module:team-chat')->name('chat.attachment');

    Route::get('/approvals', ApprovalInbox::class)->middleware('module:change-management')->name('approvals');
    Route::get('/dispatch', DispatchBoard::class)->middleware('module:field-service')->name('dispatch');

    Route::get('/kb', ArticleBrowser::class)->middleware('module:knowledge-base')->name('kb.index');
    Route::get('/kb/{article}', ArticleBrowser::class)->middleware('module:knowledge-base')->name('kb.show');

    Route::get('/team/{team}/dashboard', TeamDashboard::class)->middleware('module:reporting')->name('team.dashboard');
    Route::get('/team/{team}/settings', TeamSettings::class)->name('team.settings');
    Route::get('/team/{team}/settings/canned-responses', CannedResponseManager::class)->name('team.canned-responses');
    Route::get('/team/{team}/settings/api-keys', ApiKeyManager::class)->name('team.api-keys');
    Route::get('/team/{team}/settings/git-issues', GitIssueConnectionManager::class)->name('team.git-issues');
    Route::get('/team/{team}/settings/whatsapp', WhatsappAccountManager::class)->middleware('module:whatsapp')->name('team.whatsapp');
    Route::get('/team/{team}/settings/ai', AiSettingsManager::class)->middleware('module:ai-agent')->name('team.ai');
    Route::get('/team/{team}/settings/erp', ErpConnectionManager::class)->middleware('module:erp-integration')->name('team.erp');
});
