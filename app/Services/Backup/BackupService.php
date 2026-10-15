<?php

namespace App\Services\Backup;

use App\Models\Setting;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use RuntimeException;
use ZipArchive;

/**
 * Database backups as AES-256 encrypted ZIP plus a separate password file.
 * Only the newest backup is kept on the server, because it holds personal data.
 */
class BackupService
{
    // Key name predates counting created backups; kept so existing timestamps stay valid.
    public const LAST_BACKUP_KEY = 'backup.last_downloaded_at';

    public const LAST_SCHEDULED_KEY = 'backup.scheduled.last_run_at';

    public const REMINDER_DAYS = 7;

    private const DUMP_ENTRY = 'custovis-dump.sql';

    public function __construct(private DatabaseDumper $dumper) {}

    public function create(): string
    {
        File::ensureDirectoryExists($this->directory());
        File::cleanDirectory($this->directory());

        return $this->writeTo($this->directory());
    }

    /**
     * Writes "<name>.zip" and "<name>-passwort.txt" into the directory.
     */
    public function writeTo(string $directory): string
    {
        $name = 'custovis-backup-'.now()->format('Y-m-d-His');
        $password = Str::random(32);
        $dumpPath = $directory.'/'.self::DUMP_ENTRY;

        try {
            $this->dumper->dump($dumpPath);
            $this->zip($dumpPath, $directory."/{$name}.zip", $password);
        } finally {
            File::delete($dumpPath);
        }

        File::put($directory."/{$name}-passwort.txt", "Custovis-Datensicherung {$name}.zip\nPasswort: {$password}\n");

        return $name;
    }

    /**
     * @return array{name: string, archive: string, password: string, created_at: Carbon}|null
     */
    public function latest(): ?array
    {
        $archive = collect(File::glob($this->directory().'/custovis-backup-*.zip'))->last();
        if ($archive === null) {
            return null;
        }

        $name = basename($archive, '.zip');

        return [
            'name' => $name,
            'archive' => $archive,
            'password' => $this->directory()."/{$name}-passwort.txt",
            'created_at' => Carbon::createFromTimestamp(File::lastModified($archive)),
        ];
    }

    public function deleteStored(): void
    {
        File::deleteDirectory($this->directory());
    }

    public function markBackedUp(): void
    {
        Setting::write(self::LAST_BACKUP_KEY, now()->toIso8601String());
    }

    public function lastBackupAt(): ?Carbon
    {
        return $this->timestamp(self::LAST_BACKUP_KEY);
    }

    public function timestamp(string $key): ?Carbon
    {
        $value = Setting::read($key);

        return $value ? Carbon::parse($value) : null;
    }

    /**
     * A created, downloaded or scheduled backup counts as "gesichert"; deleting
     * the file from the server afterwards does not reset the reminder interval.
     */
    public function isOverdue(): bool
    {
        $last = collect([$this->lastBackupAt(), $this->timestamp(self::LAST_SCHEDULED_KEY)])->filter()->max();

        return $last === null || $last->lt(now()->subDays(self::REMINDER_DAYS));
    }

    /**
     * Archive, password and dump header are checked before any table is dropped.
     */
    public function restore(string $archivePath, string $passwordFileContent): void
    {
        $workDir = storage_path('app/private/backup-restore-'.Str::random(12));
        File::ensureDirectoryExists($workDir);

        try {
            $this->dumper->restore($this->extract($archivePath, $this->parsePassword($passwordFileContent), $workDir));
        } finally {
            File::deleteDirectory($workDir);
        }
    }

    private function zip(string $dumpPath, string $archivePath, string $password): void
    {
        $zip = new ZipArchive;
        if ($zip->open($archivePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Das Sicherungsarchiv konnte nicht angelegt werden.');
        }

        $zip->addFile($dumpPath, self::DUMP_ENTRY);
        $zip->setEncryptionName(self::DUMP_ENTRY, ZipArchive::EM_AES_256, $password);
        $zip->close();
    }

    private function extract(string $archivePath, string $password, string $workDir): string
    {
        $zip = new ZipArchive;
        if ($zip->open($archivePath) !== true || $zip->locateName(self::DUMP_ENTRY) === false) {
            throw new RuntimeException('Die ZIP-Datei ist keine Custovis-Datensicherung.');
        }

        $zip->setPassword($password);
        $extracted = $zip->extractTo($workDir, self::DUMP_ENTRY);
        $zip->close();

        if (! $extracted) {
            throw new RuntimeException('Das Passwort passt nicht zur Sicherung.');
        }

        return $workDir.'/'.self::DUMP_ENTRY;
    }

    private function parsePassword(string $content): string
    {
        if (preg_match('/^Passwort:\s*(\S+)/m', $content, $match)) {
            return $match[1];
        }

        $password = trim($content);
        if ($password === '' || str_contains($password, "\n")) {
            throw new RuntimeException('In der Passwort-Datei wurde kein Passwort gefunden.');
        }

        return $password;
    }

    private function directory(): string
    {
        return storage_path('app/private/backups');
    }
}
