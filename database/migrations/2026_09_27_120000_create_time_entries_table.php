<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('time_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->date('work_date');
            $table->unsignedInteger('minutes')->nullable(); // null while the timer runs
            $table->timestamp('started_at')->nullable();
            $table->string('description', 500)->nullable();
            $table->boolean('billable')->default(true);
            // Foreign key follows with the invoice_items table (32.md); set = invoiced and locked.
            $table->unsignedBigInteger('invoice_item_id')->nullable()->index();
            $table->timestamps();

            $table->index(['user_id', 'started_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('time_entries');
    }
};
