<?php

namespace App\Support;

use App\Models\User;

/**
 * Single source for the administration menu: the admin layout renders it
 * and the agent layout uses it to decide whether to link to /admin at all.
 */
class AdminNavigation
{
    /**
     * route name => [label, permission]
     */
    private const ITEMS = [
        'admin.dashboard' => ['Dashboard', 'dashboard.management.view'],
        'admin.mailboxes.index' => ['Mailboxen', 'mailboxes.manage'],
        'admin.service-catalog.index' => ['Service-Katalog', 'service_catalog.manage'],
        'admin.kb.articles.index' => ['Wissensdatenbank', 'kb.articles.manage'],
        'admin.technicians' => ['Techniker', 'technicians.manage'],
        'admin.compliance' => ['Datenschutz', 'compliance.manage'],
        'admin.settings.theme' => ['Theme', 'system.settings.manage'],
        'admin.system.migrate' => ['System', 'system.maintain'],
    ];

    /**
     * @return array<string, string> route name => label, only entries the user may open
     */
    public static function for(?User $user): array
    {
        if (! $user) {
            return [];
        }

        return collect(self::ITEMS)
            ->filter(fn (array $item) => $user->can($item[1]))
            ->map(fn (array $item) => $item[0])
            ->all();
    }

    public static function firstRoute(?User $user): ?string
    {
        return array_key_first(self::for($user));
    }
}
