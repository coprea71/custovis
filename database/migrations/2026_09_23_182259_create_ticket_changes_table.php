<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ticket_changes', function (Blueprint $table) {
            $table->foreignId('ticket_id')->primary()->constrained()->cascadeOnDelete();
            $table->string('state');
            $table->string('change_type')->default('normal'); // standard|normal|emergency
            $table->string('risk_level')->default('medium'); // low|medium|high
            $table->timestamp('planned_start')->nullable();
            $table->timestamp('planned_end')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_changes');
    }
};
