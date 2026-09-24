<?php

use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Database\Migrations\Migration;

// Re-runs the idempotent permission seeder so the ERP permission slugs
// reach existing installations via /admin/system/migrate (no shell access).
return new class extends Migration
{
    public function up(): void
    {
        (new RolesAndPermissionsSeeder)->run();
    }

    public function down(): void
    {
        //
    }
};
