<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->string('company')->nullable()->after('name');
            $table->string('country', 2)->default('DE')->after('city');
            $table->string('vat_id', 20)->nullable()->after('country');
            // BT-10 of an e-invoice; the Leitweg-ID for public-sector buyers.
            $table->string('buyer_reference', 100)->nullable()->after('vat_id');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn(['company', 'country', 'vat_id', 'buyer_reference']);
        });
    }
};
