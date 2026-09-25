<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * The separate "reopened" status is gone: reopened tickets are regular open
 * tickets again (the reopening is recorded as an internal note from now on).
 * Not reversible, the former reopened tickets can no longer be told apart.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('tickets')->where('status', 'reopened')->update(['status' => 'open']);
    }

    public function down(): void
    {
        //
    }
};
