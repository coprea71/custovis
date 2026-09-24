<?php

namespace Database\Seeders;

use App\Models\Module;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class ModuleSeeder extends Seeder
{
    /**
     * Registers every module declared under app/Modules (module.json) in
     * the modules table, disabled by default — activation is an explicit
     * admin action, never implied by the module simply existing on disk.
     */
    public function run(): void
    {
        $path = base_path('app/Modules');

        if (! File::isDirectory($path)) {
            return;
        }

        foreach (File::directories($path) as $moduleDir) {
            $manifestPath = $moduleDir.DIRECTORY_SEPARATOR.'module.json';

            if (! File::exists($manifestPath)) {
                continue;
            }

            $manifest = json_decode(File::get($manifestPath), true);

            if (! is_array($manifest) || empty($manifest['slug'])) {
                continue;
            }

            Module::query()->firstOrCreate(
                ['slug' => $manifest['slug']],
                ['name' => $manifest['name'] ?? $manifest['slug'], 'enabled' => false]
            );
        }
    }
}
