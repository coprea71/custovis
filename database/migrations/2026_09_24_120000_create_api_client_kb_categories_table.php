<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Whitelist of knowledge base categories (incl. their subcategories) a
     * MCP-enabled API key may search. No rows = no KB access (fail closed).
     */
    public function up(): void
    {
        Schema::create('api_client_kb_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('api_client_id')->constrained()->cascadeOnDelete();
            $table->foreignId('knowledge_base_category_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['api_client_id', 'knowledge_base_category_id'], 'api_client_kb_category_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_client_kb_categories');
    }
};
