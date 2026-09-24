<?php

namespace App\Http\Middleware;

use App\Services\Installation\InstallationService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Hard-disables /install once storage/installed.lock exists or the
 * database already contains users.
 */
class EnsureNotInstalled
{
    public function __construct(private readonly InstallationService $installation) {}

    public function handle(Request $request, Closure $next): Response
    {
        abort_if($this->installation->isInstalled(), 404);

        return $next($request);
    }
}
