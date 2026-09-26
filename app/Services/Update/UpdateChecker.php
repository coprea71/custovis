<?php

namespace App\Services\Update;

use App\Models\Setting;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Looks up the latest GitHub release. The result is stored as a setting so
 * admin pages never wait for GitHub; the web cron refreshes it daily.
 */
class UpdateChecker
{
    public const LATEST_KEY = 'update.latest_release';

    /**
     * @return array{version: string, url: string, download_url: ?string, digest: ?string, checked_at: string}|null
     */
    public function check(): ?array
    {
        try {
            $response = Http::acceptJson()->timeout(10)
                ->get('https://api.github.com/repos/'.self::repository().'/releases/latest');
        } catch (ConnectionException $e) {
            Log::warning('Update-Prüfung fehlgeschlagen: GitHub nicht erreichbar.');

            return $this->latest();
        }

        if (! $response->successful()) {
            Log::warning('Update-Prüfung fehlgeschlagen.', ['status' => $response->status()]);

            return $this->latest();
        }

        $release = $this->parse((array) $response->json());
        Setting::write(self::LATEST_KEY, $release === null ? null : json_encode($release));

        return $release;
    }

    /**
     * @return array{version: string, url: string, download_url: ?string, digest: ?string, checked_at: string}|null
     */
    public function latest(): ?array
    {
        $stored = json_decode((string) Setting::read(self::LATEST_KEY), true);

        return is_array($stored) ? $stored : null;
    }

    /**
     * @return array{version: string, url: string, download_url: ?string, digest: ?string, checked_at: string}|null
     */
    public function availableUpdate(): ?array
    {
        $latest = $this->latest();

        return $latest !== null && version_compare($latest['version'], self::installedVersion(), '>') ? $latest : null;
    }

    public static function installedVersion(): string
    {
        return (string) config('custovis.version');
    }

    public static function repository(): string
    {
        return (string) config('custovis.update.repository');
    }

    public static function releasePrefix(): string
    {
        return 'https://github.com/'.self::repository().'/releases/';
    }

    private function parse(array $data): ?array
    {
        $version = ltrim((string) ($data['tag_name'] ?? ''), 'v');
        $url = (string) ($data['html_url'] ?? '');

        if (! preg_match('/^\d+\.\d+\.\d+$/', $version) || ! str_starts_with($url, self::releasePrefix())) {
            return null;
        }

        // Only the upload-ready archive built by .github/workflows/release.yml is installable.
        $asset = collect($data['assets'] ?? [])->firstWhere('name', "custovis-v{$version}.zip");

        return [
            'version' => $version,
            'url' => $url,
            'download_url' => $asset['browser_download_url'] ?? null,
            'digest' => $asset['digest'] ?? null,
            'checked_at' => now()->toIso8601String(),
        ];
    }
}
