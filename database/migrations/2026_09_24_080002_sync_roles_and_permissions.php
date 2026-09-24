<?php

use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Database\Migrations\Migration;

// Re-runs the idempotent permission seeder so permission slugs added by
// later plans reach existing installations via /admin/system/migrate —
// there is no shell on staging/live to run db:seed.
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
