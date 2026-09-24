<?php

use App\Models\Module;
use Database\Seeders\ModuleSeeder;
use Illuminate\Database\Migrations\Migration;

/**
 * Module switches are enforced from 23.md on. Existing installations used
 * every feature without them, so all modules known at this point are
 * enabled — nothing disappears on update. Modules added later start
 * disabled (ModuleSeeder) and are switched on by an admin.
 */
return new class extends Migration
{
    public function up(): void
    {
        (new ModuleSeeder)->run();
        Module::query()->update(['enabled' => true]);
    }

    public function down(): void
    {
        //
    }
};
