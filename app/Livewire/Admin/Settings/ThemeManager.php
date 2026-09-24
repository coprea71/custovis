<?php

namespace App\Livewire\Admin\Settings;

use App\Models\Theme;
use App\Services\ThemeRegistry;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.admin')]
class ThemeManager extends Component
{
    public function mount(): void
    {
        Gate::authorize('system.settings.manage');
    }

    public function activate(int $themeId, ThemeRegistry $registry): void
    {
        Gate::authorize('system.settings.manage');

        $registry->activate(Theme::query()->where('active', true)->findOrFail($themeId), Auth::user());
    }

    public function render(ThemeRegistry $registry)
    {
        return view('livewire.admin.settings.theme-manager', [
            'themes' => Theme::query()->where('active', true)->orderByDesc('is_default')->orderBy('name')->get(),
            'activeThemeId' => $registry->activeTheme()?->id,
        ]);
    }
}
