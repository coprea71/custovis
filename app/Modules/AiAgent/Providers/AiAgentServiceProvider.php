<?php

namespace App\Modules\AiAgent\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class AiAgentServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $routes = __DIR__.'/../routes.php';

        if (file_exists($routes)) {
            Route::middleware('web')->group($routes);
        }
    }
}
