<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cmdb_ci_relations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('source_ci_id')->constrained('cmdb_configuration_items')->cascadeOnDelete();
            $table->foreignId('target_ci_id')->constrained('cmdb_configuration_items')->cascadeOnDelete();
            $table->string('relation_type'); // e.g. depends_on, hosts
            $table->timestamps();

            $table->unique(['source_ci_id', 'target_ci_id', 'relation_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cmdb_ci_relations');
    }
};
