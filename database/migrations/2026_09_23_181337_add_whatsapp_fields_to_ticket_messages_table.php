<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ticket_messages', function (Blueprint $table) {
            $table->string('whatsapp_message_id')->nullable()->after('message_id');
            $table->string('whatsapp_message_type')->nullable()->after('whatsapp_message_id'); // text|image|document|template
        });
    }

    public function down(): void
    {
        Schema::table('ticket_messages', function (Blueprint $table) {
            $table->dropColumn(['whatsapp_message_id', 'whatsapp_message_type']);
        });
    }
};
