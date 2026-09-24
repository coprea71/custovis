<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Default grants for the "agent" role. Applied only when a permission is
     * created for the first time, so re-running the seeder (sync migrations)
     * never re-grants something an admin revoked from the role afterwards.
     */
    private const AGENT_DEFAULTS = [
        'kb.articles.view',
        'chat.channels.view',
        'chat.global.post',
        'chat.direct.create',
    ];

    /**
     * Base roles/permissions for the foundation layer. Modules add their
     * own permission slugs when their feature work lands.
     */
    public function run(): void
    {
        $slugs = [
            'system.maintain',
            'team.manage',
            'team.api_keys.manage',
            'team.git_issues.manage',
            'mailboxes.manage',
            'team.whatsapp.manage',
            'service_catalog.manage',
            'team.ai.manage',
            'system.settings.manage',
            'dashboard.management.view',
            'kb.articles.view',
            'kb.articles.manage',
            'kb.categories.manage',
            'chat.channels.view',
            'chat.global.post',
            'chat.direct.create',
        ];

        $existing = Permission::query()->where('guard_name', 'web')->pluck('name')->all();

        $permissions = collect($slugs)->map(
            fn (string $slug) => Permission::findOrCreate($slug, 'web')
        );

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $systemAdmin = Role::findOrCreate('system_admin', 'web');
        $systemAdmin->syncPermissions($permissions);

        Role::findOrCreate('agent', 'web')->givePermissionTo(array_values(array_diff(self::AGENT_DEFAULTS, $existing)));
    }
}
