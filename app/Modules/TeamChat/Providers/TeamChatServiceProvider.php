<?php

namespace App\Modules\TeamChat\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class TeamChatServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $routes = __DIR__.'/../routes.php';

        if (file_exists($routes)) {
            Route::middleware('web')->group($routes);
        }
    }
}
