<?php

namespace App\Providers;

use App\Models\Module;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class ModuleServiceProvider extends ServiceProvider
{
    private const MODULES_PATH = 'app/Modules';

    /**
     * Register any application services.
     */
    public function register(): void
    {
        foreach ($this->resolvableModules() as $module) {
            $this->app->register($module['provider']);
        }
    }

    /**
     * Discover module.json files, resolve dependency order, and drop modules
     * whose dependency chain is cyclic or whose declared dependency is
     * missing/disabled — logged instead of crashing the boot process.
     *
     * @return array<int, array{slug: string, provider: string}>
     */
    private function resolvableModules(): array
    {
        $declared = $this->discoverModuleDeclarations();
        $enabledSlugs = $this->enabledModuleSlugs();

        $ordered = [];
        $visiting = [];
        $visited = [];

        $visit = function (string $slug) use (&$visit, &$ordered, &$visiting, &$visited, $declared) {
            if (isset($visited[$slug])) {
                return true;
            }

            if (isset($visiting[$slug])) {
                Log::warning("ModuleServiceProvider: dependency cycle detected at module [{$slug}], module disabled.");

                return false;
            }

            if (! isset($declared[$slug])) {
                Log::warning("ModuleServiceProvider: unknown dependency [{$slug}] referenced, skipping.");

                return false;
            }

            $visiting[$slug] = true;

            foreach ($declared[$slug]['dependencies'] as $dependency) {
                if (! $visit($dependency)) {
                    unset($visiting[$slug]);

                    return false;
                }
            }

            unset($visiting[$slug]);
            $visited[$slug] = true;
            $ordered[] = $declared[$slug];

            return true;
        };

        foreach (array_keys($declared) as $slug) {
            if (in_array($slug, $enabledSlugs, true)) {
                $visit($slug);
            }
        }

        return $ordered;
    }

    /**
     * @return array<string, array{slug: string, provider: string, dependencies: array<int, string>}>
     */
    private function discoverModuleDeclarations(): array
    {
        $path = base_path(self::MODULES_PATH);

        if (! File::isDirectory($path)) {
            return [];
        }

        $declarations = [];

        foreach (File::directories($path) as $moduleDir) {
            $manifestPath = $moduleDir.DIRECTORY_SEPARATOR.'module.json';

            if (! File::exists($manifestPath)) {
                continue;
            }

            $manifest = json_decode(File::get($manifestPath), true);

            if (! is_array($manifest) || empty($manifest['slug']) || empty($manifest['provider'])) {
                Log::warning("ModuleServiceProvider: invalid module.json in [{$moduleDir}], skipping.");

                continue;
            }

            $declarations[$manifest['slug']] = [
                'slug' => $manifest['slug'],
                'provider' => $manifest['provider'],
                'dependencies' => $manifest['dependencies'] ?? [],
            ];
        }

        return $declarations;
    }

    /**
     * @return array<int, string>
     */
    private function enabledModuleSlugs(): array
    {
        try {
            if (! Schema::hasTable('modules')) {
                return [];
            }

            return Module::query()->where('enabled', true)->pluck('slug')->all();
        } catch (\Throwable $e) {
            // DB not reachable yet (e.g. during install/migrate) — boot without modules.
            return [];
        }
    }
}
