<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Ends the session of a user deactivated while logged in (19.md).
 */
class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user('web');

        if ($user && ! $user->active) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return $request->expectsJson()
                ? response()->json(['message' => 'Konto deaktiviert.'], 403)
                : redirect()->route('login')->withErrors(['email' => 'Ihr Konto wurde deaktiviert.']);
        }

        return $next($request);
    }
}
