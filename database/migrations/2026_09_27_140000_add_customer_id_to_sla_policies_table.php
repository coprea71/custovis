<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// A policy belongs either to a team (default) or to a customer (contract
// SLA, takes precedence over the team's policy of the same priority).
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sla_policies', function (Blueprint $table) {
            $table->foreignId('team_id')->nullable()->change();
            $table->foreignId('customer_id')->nullable()->after('team_id')->constrained()->cascadeOnDelete();
            $table->unique(['customer_id', 'priority']);
        });
    }

    public function down(): void
    {
        Schema::table('sla_policies', function (Blueprint $table) {
            $table->dropUnique(['customer_id', 'priority']);
            $table->dropConstrainedForeignId('customer_id');
        });
    }
};
