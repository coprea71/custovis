<?php

namespace App\Http\Middleware;

use App\Services\ModuleAccess;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Route guard `module:<slug>`: a switched-off module behaves as if it did
 * not exist (404), for agents as well as for portal customers.
 */
class EnsureModuleIsAvailable
{
    public function __construct(private readonly ModuleAccess $modules) {}

    public function handle(Request $request, Closure $next, string $slug): Response
    {
        $user = $request->user('web');
        $available = $user ? $this->modules->allows($user, $slug) : $this->modules->enabled($slug);

        abort_unless($available, 404);

        return $next($request);
    }
}
