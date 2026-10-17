<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('api_clients', function (Blueprint $table) {
            $table->foreignId('mailbox_id')->nullable()->after('team_id')->constrained()->nullOnDelete();
        });

        // Existing keys send through their team's first active mailbox, like new ones by default.
        foreach (DB::table('api_clients')->get(['id', 'team_id']) as $client) {
            $mailboxId = DB::table('mailboxes')->where('team_id', $client->team_id)->where('active', true)->min('id');
            DB::table('api_clients')->where('id', $client->id)->update(['mailbox_id' => $mailboxId]);
        }

        // REST keys may now also read the status of their tickets.
        $tokens = DB::table('personal_access_tokens')->where('tokenable_type', 'App\\Models\\ApiClient')->get(['id', 'abilities']);
        foreach ($tokens as $token) {
            $abilities = json_decode((string) $token->abilities, true) ?: [];
            if (in_array('tickets.create', $abilities, true) && ! in_array('tickets.view', $abilities, true)) {
                DB::table('personal_access_tokens')->where('id', $token->id)->update(['abilities' => json_encode([...$abilities, 'tickets.view'])]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('api_clients', function (Blueprint $table) {
            $table->dropConstrainedForeignId('mailbox_id');
        });
    }
};
