<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('team_mail_layouts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('accent_color', 7)->default('#3D5E50');
            $table->string('font', 20)->default('arial');
            $table->string('logo_path')->nullable();
            $table->string('header_text')->nullable();
            $table->text('signature')->nullable();
            $table->text('footer_text')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('team_mail_layouts');
    }
};
