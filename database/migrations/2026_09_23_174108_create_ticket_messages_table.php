<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ticket_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained()->cascadeOnDelete();

            $table->string('visibility')->default('public'); // public|internal_note
            $table->string('direction')->default('incoming'); // incoming|outgoing

            $table->foreignId('author_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('author_customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->string('external_author_name')->nullable();
            $table->string('external_author_email')->nullable();

            $table->longText('body_html')->nullable();
            $table->longText('body_text')->nullable();
            $table->string('message_id')->nullable(); // Mail-Message-Id, dedup on IMAP re-fetch

            $table->timestamps();

            $table->unique(['ticket_id', 'message_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_messages');
    }
};
