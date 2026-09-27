<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->string('phone', 30)->nullable()->after('email');
            $table->string('mobile', 30)->nullable()->after('phone');
            $table->string('street')->nullable()->after('mobile');
            $table->string('postal_code', 10)->nullable()->after('street');
            $table->string('city', 100)->nullable()->after('postal_code');
            $table->text('notes')->nullable()->after('city');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn(['phone', 'mobile', 'street', 'postal_code', 'city', 'notes']);
        });
    }
};
