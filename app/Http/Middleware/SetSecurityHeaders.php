<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Baseline browser hardening for every web response (clickjacking, MIME
 * sniffing, referrer leaks, HTTPS downgrade).
 */
class SetSecurityHeaders
{
    // Report-only for now: Livewire/Alpine need inline handlers and eval, so an
    // enforced policy would break the UI before the violations are reviewed.
    private const CSP = "default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval'; "
        ."style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com; "
        ."img-src 'self' data: blob:; connect-src 'self' ws: wss:; frame-src 'self'; "
        ."frame-ancestors 'none'; base-uri 'self'; form-action 'self'; object-src 'none'";

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->add([
            'X-Frame-Options' => 'DENY',
            'X-Content-Type-Options' => 'nosniff',
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
            // The field technician app reads the device location.
            'Permissions-Policy' => 'geolocation=(self), camera=(), microphone=(), payment=()',
            'Content-Security-Policy-Report-Only' => self::CSP,
        ]);

        if ($request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }
}
