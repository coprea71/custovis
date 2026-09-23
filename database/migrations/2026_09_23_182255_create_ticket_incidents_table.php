<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ticket_incidents', function (Blueprint $table) {
            $table->foreignId('ticket_id')->primary()->constrained()->cascadeOnDelete();
            $table->string('state');
            $table->string('impact')->default('medium'); // low|medium|high
            $table->string('urgency')->default('medium'); // low|medium|high
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_incidents');
    }
};
