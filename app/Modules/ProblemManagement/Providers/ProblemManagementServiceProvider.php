<?php

namespace App\Modules\ProblemManagement\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class ProblemManagementServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $routes = __DIR__.'/../routes.php';

        if (file_exists($routes)) {
            Route::middleware('web')->group($routes);
        }
    }
}
