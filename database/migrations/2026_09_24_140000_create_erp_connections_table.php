<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Customer data itself is never persisted: lookups are pulled on demand and
// only cached briefly (data minimisation, Art. 5 (1) c GDPR).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('erp_connections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('type'); // odoo|shopware
            $table->string('base_url');
            $table->text('auth_payload'); // encrypted cast
            $table->json('field_mapping');
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['team_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('erp_connections');
    }
};
