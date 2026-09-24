<?php

namespace App\Modules\Whatsapp\Providers;

use Illuminate\Support\ServiceProvider;

/**
 * Registry entry only: the feature code lives in the core (see 23.md),
 * access is enforced via the module middleware and ModuleAccess.
 */
class WhatsappServiceProvider extends ServiceProvider {}
