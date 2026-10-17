<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('team_mail_layouts', function (Blueprint $table) {
            $table->boolean('acknowledgement_enabled')->default(true)->after('footer_text');
            $table->text('acknowledgement_text')->nullable()->after('acknowledgement_enabled');
            $table->boolean('resolved_enabled')->default(true)->after('acknowledgement_text');
            $table->text('resolved_text')->nullable()->after('resolved_enabled');
            $table->boolean('status_change_enabled')->default(false)->after('resolved_text');
            $table->text('status_change_text')->nullable()->after('status_change_enabled');
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->boolean('notify_status_changes')->default(false)->after('active');
        });

        Schema::table('ticket_messages', function (Blueprint $table) {
            $table->boolean('automatic')->default(false)->after('direction');
        });
    }

    public function down(): void
    {
        Schema::table('team_mail_layouts', function (Blueprint $table) {
            $table->dropColumn([
                'acknowledgement_enabled', 'acknowledgement_text',
                'resolved_enabled', 'resolved_text',
                'status_change_enabled', 'status_change_text',
            ]);
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn('notify_status_changes');
        });

        Schema::table('ticket_messages', function (Blueprint $table) {
            $table->dropColumn('automatic');
        });
    }
};
