<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->boolean('notify_acknowledgement')->default(true)->after('active');
            $table->boolean('notify_resolved')->default(true)->after('notify_acknowledgement');
        });

        Schema::table('tickets', function (Blueprint $table) {
            $table->boolean('auto_mails_muted')->default(false)->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn(['notify_acknowledgement', 'notify_resolved']);
        });

        Schema::table('tickets', function (Blueprint $table) {
            $table->dropColumn('auto_mails_muted');
        });
    }
};
