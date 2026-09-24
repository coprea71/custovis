<?php

namespace App\Support;

use App\Models\User;
use App\Services\ModuleAccess;

/**
 * Single source for the administration menu: the admin layout renders it
 * and the agent layout uses it to decide whether to link to /admin at all.
 */
class AdminNavigation
{
    /**
     * route name => [label, permission, module slug (optional)]
     */
    private const ITEMS = [
        'admin.dashboard' => ['Dashboard', 'dashboard.management.view', 'reporting'],
        'admin.users' => ['Nutzer', 'users.manage'],
        'admin.teams' => ['Teams', 'team.manage'],
        'admin.roles' => ['Rollen', 'roles.manage'],
        'admin.mailboxes.index' => ['Mailboxen', 'mailboxes.manage'],
        'admin.sla' => ['SLA', 'sla.manage'],
        'admin.cmdb' => ['CMDB', 'cmdb.manage', 'cmdb'],
        'admin.service-catalog.index' => ['Service-Katalog', 'service_catalog.manage', 'service-catalog'],
        'admin.kb.articles.index' => ['Wissensdatenbank', 'kb.articles.manage', 'knowledge-base'],
        'admin.technicians' => ['Techniker', 'technicians.manage', 'field-service'],
        'admin.customers' => ['Kunden', 'customers.manage'],
        'admin.compliance' => ['Datenschutz', 'compliance.manage'],
        'admin.modules' => ['Module', 'modules.manage'],
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
            ->filter(fn (array $item) => $user->can($item[1]) && (! isset($item[2]) || app(ModuleAccess::class)->allows($user, $item[2])))
            ->map(fn (array $item) => $item[0])
            ->all();
    }

    public static function firstRoute(?User $user): ?string
    {
        return array_key_first(self::for($user));
    }
}
