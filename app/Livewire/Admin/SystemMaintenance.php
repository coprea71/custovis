<?php

namespace App\Livewire\Admin;

use App\Models\AuditLog;
use App\Models\Setting;
use App\Services\Update\UpdateChecker;
use App\Services\Update\UpdateInstaller;
use App\Services\WebCronService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;
use RuntimeException;

/**
 * Schema updates without shell access (0.md/12.md): upload the new release
 * via FTP or install it from GitHub (28.md), then run pending migrations here.
 */
#[Layout('layouts.admin')]
class SystemMaintenance extends Component
{
    // Set before the redirect so the migrations run in a fresh request with the new code loaded.
    private const UPDATE_INSTALLED_KEY = 'custovis.update_installed';

    public ?string $output = null;

    public ?string $cronUrl = null;

    public function mount(): void
    {
        Gate::authorize('system.maintain');

        if (session()->has(self::UPDATE_INSTALLED_KEY)) {
            $this->output = 'Version '.session(self::UPDATE_INSTALLED_KEY).' installiert. '.$this->runMigrations();
        }
    }

    public function migrate(): void
    {
        Gate::authorize('system.maintain');

        $this->output = $this->runMigrations();
    }

    public function checkForUpdate(UpdateChecker $checker): void
    {
        Gate::authorize('system.maintain');

        $checker->check();
        $this->output = $checker->availableUpdate() === null ? 'Custovis ist auf dem neuesten Stand.' : null;
    }

    public function installUpdate(UpdateChecker $checker, UpdateInstaller $installer): void
    {
        Gate::authorize('system.maintain');
        $release = $checker->availableUpdate();

        if ($release === null) {
            $this->output = 'Kein Update verfügbar.';

            return;
        }

        // Download and copy must finish even if the browser gives up waiting.
        ignore_user_abort(true);
        set_time_limit(600);

        try {
            $installer->install($release);
        } catch (RuntimeException $e) {
            $this->output = $e->getMessage();

            return;
        }

        AuditLog::record('system.updated', Auth::user(), null, null, [
            'from' => UpdateChecker::installedVersion(),
            'to' => $release['version'],
        ]);
        session()->flash(self::UPDATE_INSTALLED_KEY, $release['version']);
        $this->redirectRoute('admin.system.migrate');
    }

    public function regenerateCronUrl(WebCronService $cron): void
    {
        Gate::authorize('system.maintain');

        $this->cronUrl = route('web-cron', $cron->regenerateToken());
        AuditLog::record('system.web_cron_token_regenerated', Auth::user(), null);
    }

    public function render(WebCronService $cron, UpdateChecker $checker)
    {
        return view('livewire.admin.system-maintenance', [
            'pending' => $this->pendingMigrations(),
            'version' => UpdateChecker::installedVersion(),
            'update' => $checker->availableUpdate(),
            'lastCheck' => $this->lastUpdateCheck($checker),
            'cronConfigured' => $cron->isConfigured(),
            'cronLastRun' => $this->cronLastRun(),
        ]);
    }

    private function runMigrations(): string
    {
        $pending = $this->pendingMigrations();

        Artisan::call('migrate', ['--force' => true]);
        Artisan::call('optimize:clear');
        AuditLog::record('system.migrated', Auth::user(), null, null, ['migrations' => $pending]);

        return $pending === [] ? 'Keine ausstehenden Migrationen.' : count($pending).' Migration(en) ausgeführt.';
    }

    /**
     * @return array<int, string>
     */
    private function pendingMigrations(): array
    {
        $migrator = app('migrator');

        if (! $migrator->repositoryExists()) {
            return [];
        }

        $files = $migrator->getMigrationFiles([database_path('migrations')]);

        return array_values(array_diff(array_keys($files), $migrator->getRepository()->getRan()));
    }

    private function lastUpdateCheck(UpdateChecker $checker): ?Carbon
    {
        $checkedAt = $checker->latest()['checked_at'] ?? null;

        return $checkedAt === null ? null : Carbon::parse($checkedAt);
    }

    private function cronLastRun(): ?Carbon
    {
        $lastRun = Setting::read(WebCronService::LAST_RUN_KEY);

        return $lastRun === null ? null : Carbon::parse($lastRun);
    }
}
