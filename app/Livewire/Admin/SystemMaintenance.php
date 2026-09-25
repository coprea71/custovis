<?php

namespace App\Livewire\Admin;

use App\Models\AuditLog;
use App\Models\Setting;
use App\Services\WebCronService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Schema updates without shell access (0.md/12.md): upload the new release
 * via FTP, then run pending migrations here.
 */
#[Layout('layouts.admin')]
class SystemMaintenance extends Component
{
    public ?string $output = null;

    public ?string $cronUrl = null;

    public function mount(): void
    {
        Gate::authorize('system.maintain');
    }

    public function migrate(): void
    {
        Gate::authorize('system.maintain');
        $pending = $this->pendingMigrations();

        Artisan::call('migrate', ['--force' => true]);
        Artisan::call('optimize:clear');

        $this->output = $pending === [] ? 'Keine ausstehenden Migrationen.' : count($pending).' Migration(en) ausgeführt.';
        AuditLog::record('system.migrated', Auth::user(), null, null, ['migrations' => $pending]);
    }

    public function regenerateCronUrl(WebCronService $cron): void
    {
        Gate::authorize('system.maintain');

        $this->cronUrl = route('web-cron', $cron->regenerateToken());
        AuditLog::record('system.web_cron_token_regenerated', Auth::user(), null);
    }

    public function render(WebCronService $cron)
    {
        return view('livewire.admin.system-maintenance', [
            'pending' => $this->pendingMigrations(),
            'version' => config('custovis.version'),
            'cronConfigured' => $cron->isConfigured(),
            'cronLastRun' => $this->cronLastRun(),
        ]);
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

    private function cronLastRun(): ?Carbon
    {
        $lastRun = Setting::read(WebCronService::LAST_RUN_KEY);

        return $lastRun === null ? null : Carbon::parse($lastRun);
    }
}
