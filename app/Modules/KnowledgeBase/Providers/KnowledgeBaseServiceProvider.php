<?php

namespace App\Modules\KnowledgeBase\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class KnowledgeBaseServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $routes = __DIR__.'/../routes.php';

        if (file_exists($routes)) {
            Route::middleware('web')->group($routes);
        }
    }
}
