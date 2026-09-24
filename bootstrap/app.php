<?php

use App\Http\Controllers\Api\McpHealthController;
use App\Http\Controllers\Webhooks\WhatsappWebhookController;
use App\Http\Middleware\EnsureTwoFactorIsConfirmed;
use App\Mcp\Servers\CustovisServer;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Laravel\Mcp\Facades\Mcp;
use Laravel\Sanctum\Http\Middleware\CheckAbilities;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
        then: function () {
            // 2FA is enforced for every agent-facing area (11.md).
            Route::middleware(['web', EnsureTwoFactorIsConfirmed::class])
                ->prefix('agent')
                ->name('agent.')
                ->group(__DIR__.'/../routes/agent.php');

            Route::middleware(['web', EnsureTwoFactorIsConfirmed::class])
                ->prefix('field')
                ->name('field.')
                ->group(__DIR__.'/../routes/field.php');

            Route::middleware('web')
                ->prefix('portal')
                ->name('portal.')
                ->group(__DIR__.'/../routes/portal.php');

            Route::middleware(['web', EnsureTwoFactorIsConfirmed::class])
                ->prefix('admin')
                ->name('admin.')
                ->group(__DIR__.'/../routes/admin.php');

            // Outside the '/api' URL prefix on purpose (see 4.md path
            // /webhooks/whatsapp/{account}), but still under the 'api'
            // middleware GROUP (not the 'web' group) so SubstituteBindings
            // resolves {account} and route/model binding + throttling work
            // without CSRF — Meta posts here unauthenticated and is
            // verified via signature/verify-token instead.
            // Global MCP endpoint for AI phone assistants (13.md). 'api' group = no
            // session, so only bearer tokens authenticate, never an agent's cookie.
            Route::middleware('api')->group(function () {
                Mcp::web('/mcp', CustovisServer::class)
                    ->middleware(['auth:sanctum', CheckAbilities::class.':mcp.tools.use', 'throttle:mcp']);
                Route::get('/mcp/health', McpHealthController::class)->middleware('throttle:mcp-health');
            });

            Route::middleware('api')->group(function () {
                Route::get('/webhooks/whatsapp/{account}', [WhatsappWebhookController::class, 'verify']);
                Route::post('/webhooks/whatsapp/{account}', [WhatsappWebhookController::class, 'receive']);
            });
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Customers and agents have separate logins (guards customer/web).
        $middleware->redirectGuestsTo(fn (Request $request) => $request->is('portal', 'portal/*') ? route('portal.login') : route('login'));
        $middleware->redirectUsersTo(fn (Request $request) => $request->is('portal', 'portal/*') ? route('portal.tickets.index') : '/agent');

        // Must run before SubstituteBindings so {ticket} is resolved through CustomerOwnedScope.
        $middleware->prependToPriorityList(\Illuminate\Routing\Middleware\SubstituteBindings::class, \App\Http\Middleware\ScopeTicketsToCustomer::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
