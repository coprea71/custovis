<?php

use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Also re-runs the idempotent permission seeder so team.auto_mails.manage
// reaches existing installations via /admin/system/migrate (no shell on staging/live).
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            $table->boolean('auto_mails_team_admin_access')->default(true)->after('auto_assign_on_view');
        });

        (new RolesAndPermissionsSeeder)->run();
    }

    public function down(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            $table->dropColumn('auto_mails_team_admin_access');
        });
    }
};
