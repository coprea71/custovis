<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('git_issue_connections', function (Blueprint $table) {
            $table->string('base_url')->nullable()->after('repository'); // self-hosted GitLab, null = gitlab.com
        });
    }

    public function down(): void
    {
        Schema::table('git_issue_connections', function (Blueprint $table) {
            $table->dropColumn('base_url');
        });
    }
};
