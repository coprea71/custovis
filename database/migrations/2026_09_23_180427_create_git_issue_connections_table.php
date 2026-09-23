<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('git_issue_connections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->string('provider'); // github|gitlab
            $table->string('repository'); // owner/repo or GitLab project path
            $table->text('access_token'); // encrypted cast
            $table->text('webhook_secret'); // encrypted cast
            $table->string('sync_mode')->default('webhook'); // webhook|poll
            $table->timestamp('last_synced_at')->nullable();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('git_issue_connections');
    }
};
