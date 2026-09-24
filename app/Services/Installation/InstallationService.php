<?php

namespace App\Services\Installation;

use App\Models\User;
use Database\Seeders\DemoSeeder;
use Database\Seeders\ModuleSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Shared by the web installer (/install, FTP-only hosting) and the
 * custovis:install command (hosts with shell access) — see 0.md/12.md.
 */
class InstallationService
{
    public const REQUIRED_EXTENSIONS = ['pdo_mysql', 'mbstring', 'openssl', 'tokenizer', 'xml', 'ctype', 'json', 'fileinfo', 'gd', 'curl'];

    public function lockPath(): string
    {
        return config('custovis.install_lock_path');
    }

    /**
     * An instance that already has users counts as installed even without
     * the lock file (e.g. updated from a version before the installer), so
     * deploying the installer can never reopen /install on a live system.
     */
    public function isInstalled(): bool
    {
        if (File::exists($this->lockPath())) {
            return true;
        }

        try {
            $installed = Schema::hasTable('users') && User::query()->exists();
        } catch (Throwable) {
            return false; // no database configured yet
        }

        if ($installed) {
            $this->lock();
        }

        return $installed;
    }

    /**
     * @return array<string, bool> requirement label => fulfilled
     */
    public function requirements(): array
    {
        $checks = ['PHP >= 8.2' => version_compare(PHP_VERSION, '8.2.0', '>=')];

        foreach (self::REQUIRED_EXTENSIONS as $extension) {
            $checks["PHP-Erweiterung {$extension}"] = extension_loaded($extension);
        }

        foreach (['storage', 'bootstrap/cache'] as $directory) {
            $checks["Verzeichnis {$directory} beschreibbar"] = is_writable(base_path($directory));
        }

        $checks['.env beschreibbar'] = File::exists(base_path('.env')) ? is_writable(base_path('.env')) : is_writable(base_path());

        return $checks;
    }

    /**
     * @param  array{host: string, port: int|string, database: string, username: string, password: string|null}  $db
     */
    public function testDatabase(array $db): ?string
    {
        try {
            new \PDO("mysql:host={$db['host']};port={$db['port']};dbname={$db['database']}", $db['username'], (string) $db['password'], [\PDO::ATTR_TIMEOUT => 5]);

            return null;
        } catch (\PDOException $e) {
            return 'Datenbankverbindung fehlgeschlagen: '.$e->getMessage();
        }
    }

    /**
     * Runs migrations and base seeders, creates the first admin and locks
     * the installer. Idempotent migrations/seeders make a retry safe.
     *
     * @param  array{name: string, email: string, password: string}  $admin
     */
    public function install(array $admin, bool $withDemoData = false): User
    {
        Artisan::call('migrate', ['--force' => true]);
        (new RolesAndPermissionsSeeder)->run();
        (new ModuleSeeder)->run();

        $user = DB::transaction(function () use ($admin) {
            $user = User::query()->create($admin);
            $user->assignRole('system_admin');

            return $user;
        });

        if ($withDemoData) {
            (new DemoSeeder)->run();
        }

        $this->lock();

        return $user;
    }

    private function lock(): void
    {
        File::put($this->lockPath(), now()->toIso8601String());
    }
}
