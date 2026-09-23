<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cab_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained()->cascadeOnDelete();
            $table->foreignId('approver_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('decision')->default('pending'); // pending|approved|rejected
            $table->timestamp('decided_at')->nullable();
            $table->timestamps();

            $table->unique(['ticket_id', 'approver_user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cab_approvals');
    }
};
