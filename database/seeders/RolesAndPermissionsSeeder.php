<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
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
        ];

        $permissions = collect($slugs)->map(
            fn (string $slug) => Permission::findOrCreate($slug, 'web')
        );

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $systemAdmin = Role::findOrCreate('system_admin', 'web');
        $systemAdmin->syncPermissions($permissions);

        Role::findOrCreate('agent', 'web');
    }
}
