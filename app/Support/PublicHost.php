<?php

namespace App\Support;

use Closure;
use Illuminate\Http\Client\PendingRequest;
use Psr\Http\Message\RequestInterface;

/**
 * Guards user-supplied service URLs against server-side request forgery:
 * a host must not resolve to loopback, private or reserved addresses.
 */
class PublicHost
{
    /**
     * Re-checks every outgoing request (DNS may change after validation)
     * and refuses redirects so credentials never reach another host.
     *
     * @param  Closure(): \Throwable  $exception
     */
    public static function guard(PendingRequest $request, Closure $exception): PendingRequest
    {
        return $request->withoutRedirecting()->withRequestMiddleware(function (RequestInterface $psrRequest) use ($exception) {
            if (self::isInternal((string) $psrRequest->getUri())) {
                throw $exception();
            }

            return $psrRequest;
        });
    }

    public static function isInternal(string $url): bool
    {
        // Local development talks to services on localhost/LAN on purpose.
        if (app()->environment('local')) {
            return false;
        }

        $host = trim((string) parse_url($url, PHP_URL_HOST), '[]');

        if ($host === '') {
            return true;
        }

        foreach (self::resolve($host) as $ip) {
            if (! filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<string>
     */
    private static function resolve(string $host): array
    {
        if (filter_var($host, FILTER_VALIDATE_IP)) {
            return [$host];
        }

        $ips = gethostbynamel($host) ?: [];

        foreach (@dns_get_record($host, DNS_AAAA) ?: [] as $record) {
            $ips[] = $record['ipv6'];
        }

        return $ips;
    }
}
