<?php

return [

    /*
    | Directory scanned by ThemeServiceProvider for <slug>/theme.json
    | manifests (17.md). Configurable so tests can point at a temp dir.
    */
    'themes_path' => env('CUSTOVIS_THEMES_PATH', resource_path('themes')),

    'default_theme' => 'musterlayout',

];
