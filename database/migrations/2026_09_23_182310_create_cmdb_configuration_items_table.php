<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cmdb_configuration_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('type'); // e.g. server, application, network_device
            $table->string('status')->default('active'); // active|retired
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cmdb_configuration_items');
    }
};
