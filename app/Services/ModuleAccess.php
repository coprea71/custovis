<?php

namespace App\Services;

use App\Models\Module;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Module switch-on per installation, role and user (0.md "Modularität",
 * 23.md). A module is usable when it is enabled and either has no
 * role/user assignment (open to everyone) or the user is assigned directly
 * or through a role. Module access never replaces permission checks.
 */
class ModuleAccess
{
    /** @var Collection<string, Module>|null */
    private ?Collection $modules = null;

    public function enabled(string $slug): bool
    {
        return (bool) $this->module($slug)?->enabled;
    }

    public function allows(?User $user, string $slug): bool
    {
        $module = $this->module($slug);

        if (! $module?->enabled) {
            return false;
        }

        if ($module->users->isEmpty() && $module->roles->isEmpty()) {
            return true;
        }

        return $user !== null && (
            $module->users->contains('id', $user->id)
            || $module->roles->pluck('name')->intersect($user->getRoleNames())->isNotEmpty()
        );
    }

    public function flush(): void
    {
        $this->modules = null;
    }

    private function module(string $slug): ?Module
    {
        // No database yet (fresh upload before /install): every module counts as unavailable.
        $this->modules ??= rescue(fn () => Module::query()->with('users:id', 'roles:id,name')->get()->keyBy('slug'), collect(), false);

        return $this->modules->get($slug);
    }
}
