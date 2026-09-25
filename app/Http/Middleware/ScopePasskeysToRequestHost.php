<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * WebAuthn binds a passkey to exactly one domain, so on every additional
 * domain (APP_HOSTS) the relying party must be that domain instead of APP_URL.
 */
class ScopePasskeysToRequestHost
{
    public function handle(Request $request, Closure $next): Response
    {
        $host = $request->getHost();

        if (in_array($host, config('app.hosts'), true)) {
            config([
                'passkeys.relying_party_id' => $host,
                'passkeys.allowed_origins' => [$request->getSchemeAndHttpHost()],
            ]);
        }

        return $next($request);
    }
}
