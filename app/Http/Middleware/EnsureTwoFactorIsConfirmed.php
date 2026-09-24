<?php

namespace App\Http\Middleware;

use App\Support\TwoFactorRequirement;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Forces agents/admins without confirmed 2FA to the setup page before they
 * can use /agent, /admin or /field (production only, see TwoFactorRequirement).
 */
class EnsureTwoFactorIsConfirmed
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user('web');

        if ($user && TwoFactorRequirement::mandatory() && $user->two_factor_confirmed_at === null) {
            return $request->expectsJson()
                ? response()->json(['message' => 'Zwei-Faktor-Authentifizierung muss eingerichtet werden.'], 403)
                : redirect()->route('account.security')->with('status', 'two-factor-required');
        }

        return $next($request);
    }
}
