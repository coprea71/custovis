<?php

namespace App\Http\Controllers;

use App\Services\WebCronService;
use Illuminate\Http\Response;

class WebCronController extends Controller
{
    public function __invoke(string $token, WebCronService $cron): Response
    {
        abort_unless($cron->verify($token), 404);

        // Web-cron services often drop the connection early; the run must finish anyway.
        ignore_user_abort(true);
        set_time_limit(120);

        $cron->run();

        return response('OK', 200, ['Content-Type' => 'text/plain']);
    }
}
