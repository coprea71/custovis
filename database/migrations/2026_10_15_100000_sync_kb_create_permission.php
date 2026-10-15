<?php

use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Database\Migrations\Migration;

// Re-runs the idempotent permission seeder so kb.articles.create reaches
// existing installations via /admin/system/migrate (no shell on staging/live).
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
