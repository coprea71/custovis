<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Membership of team/global/ticket channels is derived at access time from
 * team_user, permissions and the ticket's team (ChatAccess) instead of a
 * copied chat_channel_members table — a copy would drift, e.g. keep chat
 * access for someone already removed from the team.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chat_channels', function (Blueprint $table) {
            $table->id();
            $table->string('type'); // team|global|ticket
            $table->foreignId('team_id')->nullable()->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('ticket_id')->nullable()->unique()->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->timestamps();

            $table->index('type');
        });

        Schema::create('chat_direct_threads', function (Blueprint $table) {
            $table->id();
            $table->string('participant_key')->unique(); // "<lowerUserId>-<higherUserId>", one thread per pair
            $table->timestamps();
        });

        Schema::create('chat_direct_thread_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('thread_id')->constrained('chat_direct_threads')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['thread_id', 'user_id']);
        });

        Schema::create('chat_messages', function (Blueprint $table) {
            $table->id();
            // exactly one of channel_id / direct_thread_id is set
            $table->foreignId('channel_id')->nullable()->constrained('chat_channels')->cascadeOnDelete();
            $table->foreignId('direct_thread_id')->nullable()->constrained('chat_direct_threads')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->text('body')->nullable();
            $table->string('attachment_disk')->nullable();
            $table->string('attachment_path')->nullable();
            $table->string('attachment_name')->nullable();
            $table->string('attachment_mime')->nullable();
            $table->unsignedBigInteger('attachment_size')->nullable();
            $table->timestamps();

            $table->index(['channel_id', 'id']);
            $table->index(['direct_thread_id', 'id']);
            $table->index('created_at'); // retention pruning
        });

        Schema::create('chat_read_states', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('channel_id')->nullable()->constrained('chat_channels')->cascadeOnDelete();
            $table->foreignId('direct_thread_id')->nullable()->constrained('chat_direct_threads')->cascadeOnDelete();
            $table->unsignedBigInteger('last_read_message_id')->default(0);
            $table->timestamps();

            $table->unique(['user_id', 'channel_id', 'direct_thread_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_read_states');
        Schema::dropIfExists('chat_messages');
        Schema::dropIfExists('chat_direct_thread_participants');
        Schema::dropIfExists('chat_direct_threads');
        Schema::dropIfExists('chat_channels');
    }
};
