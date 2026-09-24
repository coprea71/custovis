<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Setting;
use App\Models\Theme;
use App\Models\User;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class ThemeRegistry
{
    public const ACTIVE_SETTING_KEY = 'theme.active';

    /**
     * Only these custom properties may be overridden by a theme — they are
     * the Tailwind @theme tokens from resources/css/app.css. Anything else
     * in a theme.json rejects the whole theme (17.md: no arbitrary CSS).
     */
    public const CSS_VARIABLES = [
        'color-canvas',
        'color-calm-50', 'color-calm-100', 'color-calm-200', 'color-calm-300', 'color-calm-400',
        'color-calm-500', 'color-calm-600', 'color-calm-700', 'color-calm-800', 'color-calm-900',
        'color-slatecalm-50', 'color-slatecalm-100', 'color-slatecalm-200',
        'color-slatecalm-600', 'color-slatecalm-800', 'color-slatecalm-900',
        'color-ocean-50', 'color-ocean-100', 'color-ocean-500', 'color-ocean-700',
    ];

    private const COLOR_PATTERN = '/^#([0-9a-f]{3}|[0-9a-f]{6}|[0-9a-f]{8})$/i';

    private const FONT_PATTERN = "/^[A-Za-z0-9 ,'\\-]{1,120}$/";

    private const SLUG_PATTERN = '/^[a-z0-9-]{1,50}$/';

    private const PREVIEW_EXTENSIONS = ['png', 'jpg', 'jpeg', 'webp'];

    /**
     * Upserts every valid theme found on disk and flags themes whose folder
     * vanished as inactive. Invalid manifests are logged and skipped.
     */
    public function sync(): void
    {
        $found = $this->discover();

        foreach ($found as $slug => $attributes) {
            Theme::query()->updateOrCreate(['slug' => $slug], $attributes + ['active' => true]);
        }

        Theme::query()->whereNotIn('slug', array_keys($found))->update(['active' => false]);
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function discover(): array
    {
        $path = config('custovis.themes_path');

        if (! File::isDirectory($path)) {
            return [];
        }

        $themes = [];

        foreach (File::directories($path) as $dir) {
            $attributes = $this->readManifest($dir);

            if ($attributes !== null) {
                $themes[basename($dir)] = $attributes;
            }
        }

        return $themes;
    }

    public function signature(): string
    {
        $files = File::glob(config('custovis.themes_path').'/*/theme.json') ?: [];

        return md5(collect($files)->map(fn (string $file) => $file.'@'.File::lastModified($file))->implode('|'));
    }

    public function activeTheme(): ?Theme
    {
        $slug = Setting::read(self::ACTIVE_SETTING_KEY);
        $active = Theme::query()->where('active', true);

        return ($slug ? (clone $active)->where('slug', $slug)->first() : null)
            ?? (clone $active)->where('is_default', true)->first();
    }

    public function activate(Theme $theme, User $by): void
    {
        abort_unless($theme->active, 404);

        $previous = $this->activeTheme()?->slug;
        Setting::write(self::ACTIVE_SETTING_KEY, $theme->slug);

        AuditLog::record('theme.changed', $by, null, $theme, ['from' => $previous, 'to' => $theme->slug]);
    }

    /**
     * Safe to emit unescaped: slug and every value passed the whitelist
     * patterns during sync(), so no "<", ">", quotes or braces can occur.
     */
    public function css(Theme $theme): string
    {
        $declarations = collect($theme->css_variables)
            ->map(fn (string $value, string $name) => "--{$name}:{$value};")
            ->implode('');

        if ($theme->font_family) {
            $declarations .= "--font-sans:{$theme->font_family};";
        }

        return "html[data-theme=\"{$theme->slug}\"]{{$declarations}}";
    }

    public function previewPath(Theme $theme): string
    {
        return config('custovis.themes_path').DIRECTORY_SEPARATOR.$theme->slug.DIRECTORY_SEPARATOR.$theme->preview_image;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function readManifest(string $dir): ?array
    {
        $slug = basename($dir);
        $manifest = json_decode((string) @file_get_contents($dir.DIRECTORY_SEPARATOR.'theme.json'), true);
        $error = is_array($manifest) ? $this->manifestError($slug, $dir, $manifest) : 'theme.json missing or not valid JSON';

        if ($error !== null) {
            Log::warning("ThemeRegistry: theme [{$slug}] skipped — {$error}.");

            return null;
        }

        return [
            'name' => $manifest['name'],
            'preview_image' => $manifest['preview_image'],
            'css_variables' => $manifest['css_variables'],
            'font_family' => $manifest['font_family'] ?? null,
            'is_default' => $slug === config('custovis.default_theme'),
        ];
    }

    /**
     * @param  array<string, mixed>  $manifest
     */
    private function manifestError(string $slug, string $dir, array $manifest): ?string
    {
        return match (true) {
            ! preg_match(self::SLUG_PATTERN, $slug) => 'folder name is not a valid slug',
            ! is_string($manifest['name'] ?? null) || $manifest['name'] === '' => 'required field "name" missing',
            ! $this->validPreview($dir, $manifest['preview_image'] ?? null) => 'required field "preview_image" missing or invalid',
            ! $this->validVariables($manifest['css_variables'] ?? null) => 'required field "css_variables" missing or contains non-whitelisted keys/values',
            isset($manifest['font_family']) && ! preg_match(self::FONT_PATTERN, (string) $manifest['font_family']) => 'font_family contains invalid characters',
            default => null,
        };
    }

    private function validPreview(string $dir, mixed $file): bool
    {
        return is_string($file)
            && $file === basename($file)
            && in_array(strtolower(pathinfo($file, PATHINFO_EXTENSION)), self::PREVIEW_EXTENSIONS, true)
            && File::exists($dir.DIRECTORY_SEPARATOR.$file);
    }

    private function validVariables(mixed $variables): bool
    {
        if (! is_array($variables) || $variables === []) {
            return false;
        }

        foreach ($variables as $name => $value) {
            if (! in_array($name, self::CSS_VARIABLES, true) || ! is_string($value) || ! preg_match(self::COLOR_PATTERN, $value)) {
                return false;
            }
        }

        return true;
    }
}
