<?php

use App\Providers\AppServiceProvider;
use App\Providers\FortifyServiceProvider;
use App\Providers\ModuleServiceProvider;
use App\Providers\ThemeServiceProvider;
use App\Providers\VoltServiceProvider;

return [
    AppServiceProvider::class,
    FortifyServiceProvider::class,
    ModuleServiceProvider::class,
    ThemeServiceProvider::class,
    VoltServiceProvider::class,
];
