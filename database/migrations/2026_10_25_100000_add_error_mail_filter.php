<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('team_mail_layouts', function (Blueprint $table) {
            $table->boolean('error_mail_filter_enabled')->default(false)->after('status_change_text');
            $table->text('error_mail_senders')->nullable()->after('error_mail_filter_enabled');
        });

        Schema::table('tickets', function (Blueprint $table) {
            $table->unsignedSmallInteger('error_mail_status')->nullable()->after('auto_mails_muted');
            $table->index(['team_id', 'error_mail_status']);
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropIndex(['team_id', 'error_mail_status']);
            $table->dropColumn('error_mail_status');
        });

        Schema::table('team_mail_layouts', function (Blueprint $table) {
            $table->dropColumn(['error_mail_filter_enabled', 'error_mail_senders']);
        });
    }
};
