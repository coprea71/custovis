<?php

namespace App\Http\Controllers\Themes;

use App\Http\Controllers\Controller;
use App\Models\Theme;
use App\Services\ThemeRegistry;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ThemePreviewController extends Controller
{
    /**
     * Theme folders live outside public/, so previews are streamed through
     * here — only for registered themes and the file name validated at scan.
     */
    public function __invoke(Theme $theme, ThemeRegistry $registry): BinaryFileResponse
    {
        $path = $registry->previewPath($theme);

        abort_unless($theme->active && is_file($path), 404);

        return response()->file($path, ['Cache-Control' => 'public, max-age=86400']);
    }
}
