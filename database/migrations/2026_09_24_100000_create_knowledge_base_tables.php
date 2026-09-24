<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('knowledge_base_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('knowledge_base_categories')->nullOnDelete();
            $table->string('name');
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
        });

        Schema::create('knowledge_base_articles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('knowledge_base_categories')->restrictOnDelete();
            // title/body mirror the current version so the fulltext index can live on this table
            $table->string('title');
            $table->longText('body'); // Markdown
            $table->string('visibility')->default('internal'); // internal|public
            $table->unsignedBigInteger('current_version_id')->nullable()->index();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('visibility');

            // SQLite (tests) has no FULLTEXT; the search service falls back to LIKE there.
            if (DB::getDriverName() !== 'sqlite') {
                $table->fullText(['title', 'body']);
            }
        });

        Schema::create('knowledge_base_article_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('article_id')->constrained('knowledge_base_articles')->cascadeOnDelete();
            $table->unsignedInteger('version_number');
            $table->string('title');
            $table->longText('body');
            $table->string('visibility');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['article_id', 'version_number']);
        });

        Schema::create('knowledge_base_article_feedback', function (Blueprint $table) {
            $table->id();
            $table->foreignId('article_id')->constrained('knowledge_base_articles')->cascadeOnDelete();
            $table->boolean('helpful'); // anonymous on purpose: no user/customer reference
            $table->timestamps();
        });

        Schema::table('tickets', function (Blueprint $table) {
            $table->foreignId('resolved_with_article_id')->nullable()->after('tags')
                ->constrained('knowledge_base_articles')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropConstrainedForeignId('resolved_with_article_id');
        });
        Schema::dropIfExists('knowledge_base_article_feedback');
        Schema::dropIfExists('knowledge_base_article_versions');
        Schema::dropIfExists('knowledge_base_articles');
        Schema::dropIfExists('knowledge_base_categories');
    }
};
