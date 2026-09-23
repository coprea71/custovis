<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->string('use_case'); // summarize|suggest_reply|classify|embed
            $table->string('provider'); // openai|anthropic|ollama|custom
            $table->text('api_key')->nullable(); // encrypted cast, falls back to .env when null
            $table->string('endpoint_url')->nullable(); // ollama/custom
            $table->string('model')->nullable();
            $table->boolean('redact_pii')->default(false);
            $table->timestamps();

            $table->unique(['team_id', 'use_case']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_settings');
    }
};
