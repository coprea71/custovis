<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mailboxes', function (Blueprint $table) {
            $table->text('last_fetch_error')->nullable()->after('last_fetched_at');
            $table->timestamp('last_fetch_error_at')->nullable()->after('last_fetch_error');
        });
    }

    public function down(): void
    {
        Schema::table('mailboxes', function (Blueprint $table) {
            $table->dropColumn(['last_fetch_error', 'last_fetch_error_at']);
        });
    }
};
