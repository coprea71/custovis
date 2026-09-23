<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->string('display_name');
            $table->string('phone_number_id')->unique();
            $table->string('business_account_id');
            $table->text('access_token'); // encrypted cast
            $table->text('webhook_verify_token'); // encrypted cast — GET handshake
            $table->text('app_secret'); // encrypted cast — HMAC signature verification
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_accounts');
    }
};
