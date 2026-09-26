<?php

namespace App\Services\Update;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use SplFileInfo;
use ZipArchive;

/**
 * Installs a release archive without shell access: download, verify the
 * SHA-256 digest published by GitHub, extract and copy over the installation.
 * Like deploy.bat it never deletes files and never touches .env or storage/.
 */
class UpdateInstaller
{
    private const PROTECTED_PREFIXES = ['storage/', 'bootstrap/cache/', 'public/storage/', '.git/'];

    public function __construct(private ?string $targetPath = null) {}

    /**
     * @param  array{version: string, download_url: ?string, digest: ?string}  $release
     */
    public function install(array $release): void
    {
        $target = $this->targetPath ?? base_path();

        // A developer checkout must be updated via git, not overwritten.
        if (is_dir($target.'/.git')) {
            throw new RuntimeException('Diese Installation ist ein Git-Checkout. Bitte per git aktualisieren.');
        }

        $this->assertTrusted($release);

        $workDir = storage_path('app/updates/'.$release['version']);
        File::deleteDirectory($workDir);
        File::ensureDirectoryExists($workDir);

        try {
            $archive = $this->download($release, $workDir.'/release.zip');
            $this->extract($archive, $workDir.'/files');
            $this->copyInto($workDir.'/files', $target);
        } finally {
            File::deleteDirectory($workDir);
        }

        if (function_exists('opcache_reset')) {
            opcache_reset();
        }
    }

    private function assertTrusted(array $release): void
    {
        $url = (string) ($release['download_url'] ?? '');
        $digest = (string) ($release['digest'] ?? '');

        if (! str_starts_with($url, UpdateChecker::releasePrefix().'download/')) {
            throw new RuntimeException('Für diese Version gibt es kein installierbares Release-Archiv.');
        }

        if (! preg_match('/^sha256:[0-9a-f]{64}$/', $digest)) {
            throw new RuntimeException('Das Release-Archiv hat keine Prüfsumme und wird nicht installiert.');
        }
    }

    private function download(array $release, string $path): string
    {
        $response = Http::timeout(300)->sink($path)->get($release['download_url']);

        if (! $response->successful()) {
            throw new RuntimeException('Download des Release-Archivs fehlgeschlagen (HTTP '.$response->status().').');
        }

        if (! hash_equals(substr($release['digest'], 7), hash_file('sha256', $path))) {
            throw new RuntimeException('Die Prüfsumme des Release-Archivs stimmt nicht. Update abgebrochen.');
        }

        return $path;
    }

    private function extract(string $archive, string $destination): void
    {
        $zip = new ZipArchive;

        if ($zip->open($archive) !== true) {
            throw new RuntimeException('Das Release-Archiv ist beschädigt.');
        }

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = str_replace('\\', '/', (string) $zip->getNameIndex($i));

            if (str_starts_with($name, '/') || str_contains($name, ':') || in_array('..', explode('/', $name), true)) {
                $zip->close();
                throw new RuntimeException('Das Release-Archiv enthält unzulässige Pfade.');
            }
        }

        $extracted = $zip->extractTo($destination);
        $zip->close();

        if (! $extracted) {
            throw new RuntimeException('Das Release-Archiv konnte nicht entpackt werden.');
        }
    }

    private function copyInto(string $source, string $target): void
    {
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($source, RecursiveDirectoryIterator::SKIP_DOTS));

        /** @var SplFileInfo $file */
        foreach ($files as $file) {
            $relative = str_replace('\\', '/', substr($file->getPathname(), strlen($source) + 1));

            if ($file->isDir() || $this->isProtected($relative)) {
                continue;
            }

            File::ensureDirectoryExists(dirname($target.'/'.$relative));

            if (! File::copy($file->getPathname(), $target.'/'.$relative)) {
                throw new RuntimeException("Datei konnte nicht geschrieben werden: {$relative}");
            }
        }
    }

    private function isProtected(string $relative): bool
    {
        if (str_starts_with(basename($relative), '.env') && ! str_contains($relative, '/')) {
            return $relative !== '.env.example';
        }

        foreach (self::PROTECTED_PREFIXES as $prefix) {
            if (str_starts_with($relative, $prefix)) {
                return true;
            }
        }

        return false;
    }
}
