<?php

namespace App\Support;

/**
 * Reverb needs a long-running WebSocket server, which plain shared hosting
 * cannot provide. Without a configured key the app falls back to polling.
 */
final class Realtime
{
    public static function enabled(): bool
    {
        return config('broadcasting.default') === 'reverb'
            && filled(config('broadcasting.connections.reverb.key'));
    }

    /**
     * Public connection data for laravel-echo, read at runtime so release
     * builds work with any server; the secret never leaves the server.
     *
     * @return array{key: string, host: string, port: int, scheme: string}|null
     */
    public static function clientConfig(): ?array
    {
        if (! self::enabled()) {
            return null;
        }

        $options = config('broadcasting.connections.reverb.options');

        return [
            'key' => config('broadcasting.connections.reverb.key'),
            'host' => $options['host'],
            'port' => (int) $options['port'],
            'scheme' => $options['scheme'],
        ];
    }
}
