<?php

namespace App\Services\Backup;

use App\Models\AuditLog;
use App\Models\Setting;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * Daily backup into a server directory, driven by cron or web cron. The
 * scheduler calls it every minute because a web cron may hit any minute;
 * the 24-hour check here decides whether a backup is actually due.
 */
class ScheduledBackup
{
    public const ENABLED_KEY = 'backup.scheduled.enabled';

    public const DIRECTORY_KEY = 'backup.scheduled.directory';

    public const KEEP_KEY = 'backup.scheduled.keep';

    public const ERROR_KEY = 'backup.scheduled.last_error';

    private const ATTEMPT_KEY = 'backup.scheduled.last_attempt_at';

    public const DEFAULT_KEEP = 7;

    private const INTERVAL_HOURS = 24;

    public function __construct(private BackupService $backups) {}

    public function runIfDue(): void
    {
        if (! $this->enabled() || ! $this->isDue()) {
            return;
        }

        // Written first, so a failing backup is retried a day later instead of every minute.
        Setting::write(self::ATTEMPT_KEY, now()->toIso8601String());

        try {
            $directory = $this->directory();
            File::ensureDirectoryExists($directory);
            $name = $this->backups->writeTo($directory);
            $this->prune($directory);
        } catch (Throwable $e) {
            Setting::write(self::ERROR_KEY, Str::limit($e->getMessage(), 500));
            Log::error('Scheduled backup failed: '.$e->getMessage());

            return;
        }

        Setting::write(BackupService::LAST_SCHEDULED_KEY, now()->toIso8601String());
        Setting::write(self::ERROR_KEY, null);
        AuditLog::record('system.backup_scheduled', null, null, null, ['name' => $name]);
    }

    public function enabled(): bool
    {
        return Setting::read(self::ENABLED_KEY) === '1';
    }

    public function directory(): string
    {
        return Setting::read(self::DIRECTORY_KEY) ?: self::defaultDirectory();
    }

    public function keep(): int
    {
        return (int) (Setting::read(self::KEEP_KEY) ?: self::DEFAULT_KEEP);
    }

    public static function defaultDirectory(): string
    {
        return storage_path('app/private/backups-scheduled');
    }

    private function isDue(): bool
    {
        $lastAttempt = $this->backups->timestamp(self::ATTEMPT_KEY);

        return $lastAttempt === null || $lastAttempt->lte(now()->subHours(self::INTERVAL_HOURS));
    }

    /**
     * Keeps the newest backups; file names sort chronologically.
     */
    private function prune(string $directory): void
    {
        collect(File::glob($directory.'/custovis-backup-*.zip'))->sort()->reverse()->slice($this->keep())
            ->each(function (string $archive) {
                File::delete([$archive, substr($archive, 0, -4).'-passwort.txt']);
            });
    }
}
