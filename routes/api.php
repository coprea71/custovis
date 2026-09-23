<?php

use App\Http\Controllers\Api\V1\TicketApiController;
use App\Http\Controllers\Webhooks\GithubIssueWebhookController;
use App\Http\Controllers\Webhooks\GitlabIssueWebhookController;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\Http\Middleware\CheckAbilities;

Route::prefix('v1')->group(function () {
    Route::middleware(['auth:sanctum', CheckAbilities::class.':tickets.create', 'throttle:api-tickets'])
        ->post('/tickets', TicketApiController::class);

    // Signature/token-verified in the controller — no Sanctum guard, since
    // GitHub/GitLab authenticate the webhook via HMAC signature / shared
    // token, not a bearer token (see 3.md, Zusatz: GitHub-/GitLab-Issue-Import).
    Route::post('/git-issues/github/{connection}', GithubIssueWebhookController::class);
    Route::post('/git-issues/gitlab/{connection}', GitlabIssueWebhookController::class);
});
