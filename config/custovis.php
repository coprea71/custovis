<?php

return [

    'version' => '0.2.0',

    /*
    | Update check (28.md): GitHub repository whose releases carry the
    | upload-ready archive built by .github/workflows/release.yml.
    */
    'update' => [
        'repository' => env('CUSTOVIS_UPDATE_REPOSITORY', 'coprea71/custovis'),
    ],

    // Presence of this file permanently disables the web installer (12.md).
    'install_lock_path' => storage_path('installed.lock'),

    /*
    | Directory scanned by ThemeServiceProvider for <slug>/theme.json
    | manifests (17.md). Configurable so tests can point at a temp dir.
    */
    'themes_path' => env('CUSTOVIS_THEMES_PATH', resource_path('themes')),

    'default_theme' => 'musterlayout',

    /*
    | Developer/test mode (11.md): keeps 2FA optional outside local/testing,
    | e.g. on a demo instance. Never enable on a production system.
    */
    'dev_mode' => (bool) env('CUSTOVIS_DEV_MODE', false),

    /*
    | Field service (14.md): self-hosted OSRM/Nominatim keep addresses and
    | routes on own/EU infrastructure. Without URLs the RoutingService falls
    | back to a straight-line estimate and geocoding is skipped.
    */
    'field_service' => [
        'osrm_url' => env('CUSTOVIS_OSRM_URL'),
        'nominatim_url' => env('CUSTOVIS_NOMINATIM_URL'),
        'tile_url' => env('CUSTOVIS_TILE_URL', 'https://tile.openstreetmap.org/{z}/{x}/{y}.png'),
        'location_retention_days' => (int) env('CUSTOVIS_LOCATION_RETENTION_DAYS', 30),
        'fallback_speed_kmh' => 50,
    ],

];
