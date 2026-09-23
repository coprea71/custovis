<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->foreignId('sla_policy_id')->nullable()->after('priority')->constrained()->nullOnDelete();
            $table->timestamp('sla_response_due_at')->nullable()->after('sla_policy_id');
            $table->timestamp('sla_resolution_due_at')->nullable()->after('sla_response_due_at');
            $table->timestamp('sla_breached_at')->nullable()->after('sla_resolution_due_at');
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropConstrainedForeignId('sla_policy_id');
            $table->dropColumn(['sla_response_due_at', 'sla_resolution_due_at', 'sla_breached_at']);
        });
    }
};
