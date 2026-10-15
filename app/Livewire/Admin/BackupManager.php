<?php

namespace App\Livewire\Admin;

use App\Models\AuditLog;
use App\Models\Setting;
use App\Services\Backup\BackupService;
use App\Services\Backup\ScheduledBackup;
use Closure;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

#[Layout('layouts.admin')]
class BackupManager extends Component
{
    public ?string $output = null;

    /** Set from the redirect of BackupRestoreController, a plain form post. */
    public ?string $restoreError = null;

    public bool $scheduledEnabled = false;

    public string $scheduledDirectory = '';

    public int $scheduledKeep = ScheduledBackup::DEFAULT_KEEP;

    public function mount(): void
    {
        Gate::authorize('system.maintain');
        $this->restoreError = session('errors')?->first();

        $scheduled = app(ScheduledBackup::class);
        $this->scheduledEnabled = $scheduled->enabled();
        $this->scheduledDirectory = (string) Setting::read(ScheduledBackup::DIRECTORY_KEY);
        $this->scheduledKeep = $scheduled->keep();
    }

    public function saveSchedule(): void
    {
        Gate::authorize('system.maintain');

        $this->scheduledDirectory = rtrim(trim($this->scheduledDirectory), '/\\');
        $this->validate([
            'scheduledEnabled' => ['boolean'],
            'scheduledDirectory' => ['nullable', 'string', 'max:500', $this->safeDirectoryRule()],
            'scheduledKeep' => ['required', 'integer', 'min:1', 'max:365'],
        ], [], ['scheduledDirectory' => 'Verzeichnis', 'scheduledKeep' => 'Anzahl']);

        if (! $this->isWritableDirectory($this->scheduledDirectory ?: ScheduledBackup::defaultDirectory())) {
            $this->addError('scheduledDirectory', 'Das Verzeichnis lässt sich nicht anlegen oder ist nicht beschreibbar.');

            return;
        }

        Setting::write(ScheduledBackup::ENABLED_KEY, $this->scheduledEnabled ? '1' : '0');
        Setting::write(ScheduledBackup::DIRECTORY_KEY, $this->scheduledDirectory ?: null);
        Setting::write(ScheduledBackup::KEEP_KEY, (string) $this->scheduledKeep);
        AuditLog::record('system.backup_schedule_updated', Auth::user(), null, null, ['enabled' => $this->scheduledEnabled, 'keep' => $this->scheduledKeep]);
        $this->output = 'Einstellungen der automatischen Sicherung gespeichert.';
    }

    /**
     * Absolute path without "..", never below the web root where the
     * archives could be downloaded by anyone.
     */
    private function safeDirectoryRule(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            $path = str_replace('\\', '/', (string) $value);

            if (! preg_match('#^(/|[A-Za-z]:/)#', $path) || in_array('..', explode('/', $path), true)) {
                $fail('Bitte einen absoluten Pfad ohne ".." angeben.');
            } elseif (str_starts_with($path.'/', str_replace('\\', '/', public_path()).'/')) {
                $fail('Das Verzeichnis darf nicht im öffentlichen Web-Verzeichnis liegen.');
            }
        };
    }

    private function isWritableDirectory(string $directory): bool
    {
        try {
            File::ensureDirectoryExists($directory);
        } catch (Throwable) {
            return false;
        }

        return File::isWritable($directory);
    }

    public function create(BackupService $backups): void
    {
        Gate::authorize('system.maintain');
        set_time_limit(600);

        $name = $backups->create();
        $backups->markBackedUp();
        AuditLog::record('system.backup_created', Auth::user(), null, null, ['name' => $name]);
        $this->output = 'Sicherung erstellt. Bitte ZIP-Datei und Passwort-Datei herunterladen und getrennt aufbewahren.';
    }

    public function downloadArchive(BackupService $backups): BinaryFileResponse
    {
        $backup = $this->latestOrFail($backups);
        $backups->markBackedUp();
        AuditLog::record('system.backup_downloaded', Auth::user(), null, null, ['name' => $backup['name']]);

        return response()->download($backup['archive']);
    }

    public function downloadPassword(BackupService $backups): BinaryFileResponse
    {
        return response()->download($this->latestOrFail($backups)['password']);
    }

    public function deleteStored(BackupService $backups): void
    {
        Gate::authorize('system.maintain');

        $backups->deleteStored();
        AuditLog::record('system.backup_deleted', Auth::user(), null);
        $this->output = 'Sicherung vom Server gelöscht.';
    }

    public function render(BackupService $backups)
    {
        return view('livewire.admin.backup-manager', [
            'backup' => $backups->latest(),
            'lastBackupAt' => $backups->lastBackupAt(),
            'overdue' => $backups->isOverdue(),
            'scheduledLastRunAt' => $backups->timestamp(BackupService::LAST_SCHEDULED_KEY),
            'scheduledError' => Setting::read(ScheduledBackup::ERROR_KEY),
            'defaultDirectory' => ScheduledBackup::defaultDirectory(),
        ]);
    }

    /**
     * @return array{name: string, archive: string, password: string, created_at: Carbon}
     */
    private function latestOrFail(BackupService $backups): array
    {
        Gate::authorize('system.maintain');

        return $backups->latest() ?? abort(404);
    }
}
