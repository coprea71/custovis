<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Unauthenticated liveness probe for phone-assistant providers (13.md).
 * Reports component states only — no versions, counts or error details.
 */
class McpHealthController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $database = $this->check(fn () => DB::select('select 1'));
        $auth = $this->check(fn () => DB::table('personal_access_tokens')->limit(1)->exists());
        $healthy = $database && $auth;

        return response()->json([
            'status' => $healthy ? 'ok' : 'degraded',
            'database' => $database ? 'ok' : 'error',
            'auth' => $auth ? 'ok' : 'error',
        ], $healthy ? 200 : 503);
    }

    private function check(callable $probe): bool
    {
        try {
            $probe();

            return true;
        } catch (Throwable) {
            return false;
        }
    }
}
