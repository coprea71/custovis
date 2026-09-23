<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('mailbox_id')->nullable()->constrained()->nullOnDelete();

            $table->string('type')->default('support_ticket'); // support_ticket|incident|problem|change|service_request
            $table->string('source'); // mailbox|api|whatsapp|git_issue
            $table->string('external_ref')->nullable()->index(); // dedup for imported tickets (e.g. git issues)

            $table->string('subject');
            $table->string('status')->default('open'); // open|pending|closed
            $table->string('priority')->default('normal'); // low|normal|high|urgent

            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->string('requester_email')->nullable();
            $table->string('requester_name')->nullable();

            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamp('closed_at')->nullable();
            $table->timestamps();

            $table->unique(['source', 'external_ref']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
