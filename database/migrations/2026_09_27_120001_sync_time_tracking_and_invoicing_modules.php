<?php

use Database\Seeders\ModuleSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Database\Migrations\Migration;

// Registers the new modules (disabled until an admin switches them on) and
// their permissions via /admin/system/migrate (no shell on the server).
return new class extends Migration
{
    public function up(): void
    {
        (new ModuleSeeder)->run();
        (new RolesAndPermissionsSeeder)->run();
    }

    public function down(): void
    {
        //
    }
};
