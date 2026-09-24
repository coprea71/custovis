<?php

return [

    /*
    | Directory scanned by ThemeServiceProvider for <slug>/theme.json
    | manifests (17.md). Configurable so tests can point at a temp dir.
    */
    'themes_path' => env('CUSTOVIS_THEMES_PATH', resource_path('themes')),

    'default_theme' => 'musterlayout',

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
