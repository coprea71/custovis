<?php

namespace App\Services\FieldService;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Distance/duration via self-hosted OSRM and geocoding via self-hosted
 * Nominatim (14.md). Every coordinate coming back from those services is
 * range-checked before use; failures degrade to a straight-line estimate
 * so dispatching keeps working when routing is down.
 */
class RoutingService
{
    private const TIMEOUT_SECONDS = 5;

    private const ROAD_FACTOR = 1.3; // straight line -> typical road distance

    /**
     * @return array{lat: float, lng: float}|null
     */
    public function geocode(string $address): ?array
    {
        $baseUrl = config('custovis.field_service.nominatim_url');

        if (! $baseUrl || trim($address) === '') {
            return null;
        }

        try {
            $hit = Http::timeout(self::TIMEOUT_SECONDS)
                ->withUserAgent(config('app.name').' field-service')
                ->get(rtrim($baseUrl, '/').'/search', ['q' => $address, 'format' => 'json', 'limit' => 1])
                ->throw()
                ->json('0');
        } catch (Throwable $e) {
            Log::warning('RoutingService: geocoding failed', ['exception' => $e::class]);

            return null;
        }

        return is_array($hit) ? self::validCoordinates($hit['lat'] ?? null, $hit['lon'] ?? null) : null;
    }

    /**
     * @return array{meters: int, seconds: int, source: 'osrm'|'estimate'}
     */
    public function distance(float $fromLat, float $fromLng, float $toLat, float $toLng): array
    {
        // Road distances barely change; caching OSRM answers keeps the dispatcher
        // ranking from firing one request per technician on every render.
        $key = 'routing:'.implode(',', array_map(fn (float $v) => round($v, 4), [$fromLat, $fromLng, $toLat, $toLng]));

        $route = Cache::get($key) ?? $this->osrmDistance($fromLat, $fromLng, $toLat, $toLng);

        if ($route === null) {
            return $this->estimate($fromLat, $fromLng, $toLat, $toLng); // not cached: retry OSRM next time
        }

        Cache::put($key, $route, now()->addDay());

        return $route;
    }

    /**
     * @return array{lat: float, lng: float}|null
     */
    public static function validCoordinates(mixed $lat, mixed $lng): ?array
    {
        if (! is_numeric($lat) || ! is_numeric($lng)) {
            return null;
        }

        $lat = (float) $lat;
        $lng = (float) $lng;

        return ($lat >= -90 && $lat <= 90 && $lng >= -180 && $lng <= 180) ? ['lat' => $lat, 'lng' => $lng] : null;
    }

    /**
     * @return array{meters: int, seconds: int, source: 'osrm'}|null
     */
    private function osrmDistance(float $fromLat, float $fromLng, float $toLat, float $toLng): ?array
    {
        $baseUrl = config('custovis.field_service.osrm_url');

        if (! $baseUrl) {
            return null;
        }

        try {
            $route = Http::timeout(self::TIMEOUT_SECONDS)
                ->get(rtrim($baseUrl, '/')."/route/v1/driving/{$fromLng},{$fromLat};{$toLng},{$toLat}", ['overview' => 'false'])
                ->throw()
                ->json('routes.0');
        } catch (Throwable $e) {
            Log::warning('RoutingService: OSRM request failed', ['exception' => $e::class]);

            return null;
        }

        // Plausibility: reject negative or absurd (> 5,000 km / > 5 days) answers.
        if (! is_array($route) || ! is_numeric($route['distance'] ?? null) || ! is_numeric($route['duration'] ?? null)
            || $route['distance'] < 0 || $route['distance'] > 5_000_000 || $route['duration'] < 0 || $route['duration'] > 432_000) {
            return null;
        }

        return ['meters' => (int) round($route['distance']), 'seconds' => (int) round($route['duration']), 'source' => 'osrm'];
    }

    /**
     * @return array{meters: int, seconds: int, source: 'estimate'}
     */
    private function estimate(float $fromLat, float $fromLng, float $toLat, float $toLng): array
    {
        $earthRadius = 6_371_000;
        $dLat = deg2rad($toLat - $fromLat);
        $dLng = deg2rad($toLng - $fromLng);
        $a = sin($dLat / 2) ** 2 + cos(deg2rad($fromLat)) * cos(deg2rad($toLat)) * sin($dLng / 2) ** 2;
        $meters = 2 * $earthRadius * asin(min(1, sqrt($a))) * self::ROAD_FACTOR;
        $speed = config('custovis.field_service.fallback_speed_kmh') / 3.6;

        return ['meters' => (int) round($meters), 'seconds' => (int) round($meters / $speed), 'source' => 'estimate'];
    }
}
