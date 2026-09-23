<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ticket_problems', function (Blueprint $table) {
            $table->foreignId('ticket_id')->primary()->constrained()->cascadeOnDelete();
            $table->string('state');
            $table->text('root_cause')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_problems');
    }
};
