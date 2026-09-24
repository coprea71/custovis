<?php

namespace App\Providers;

use App\Services\ThemeRegistry;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class ThemeServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ThemeRegistry::class);
    }

    public function boot(): void
    {
        $this->syncThemesIfChanged();

        View::composer('components.layouts.app', function ($view) {
            $registry = $this->app->make(ThemeRegistry::class);
            $theme = rescue(fn () => $registry->activeTheme(), null, false);

            $view->with('activeTheme', $theme)
                ->with('themeCss', $theme ? $registry->css($theme) : '');
        });
    }

    /**
     * Re-scans only when a theme.json was added/changed/removed, so a normal
     * request costs one glob instead of a DB upsert per theme.
     */
    private function syncThemesIfChanged(): void
    {
        try {
            if (! Schema::hasTable('themes')) {
                return;
            }

            $registry = $this->app->make(ThemeRegistry::class);
            $signature = $registry->signature();

            if (Cache::get('themes.signature') !== $signature) {
                $registry->sync();
                Cache::forever('themes.signature', $signature);
            }
        } catch (\Throwable $e) {
            // DB not reachable yet (e.g. during install/migrate) — boot without themes.
        }
    }
}
