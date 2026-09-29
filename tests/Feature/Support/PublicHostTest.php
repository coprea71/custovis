<?php

namespace Tests\Feature\Support;

use App\Support\PublicHost;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PublicHostTest extends TestCase
{
    /**
     * @return array<string, array{string}>
     */
    public static function internalUrls(): array
    {
        return [
            'loopback' => ['https://127.0.0.1/api'],
            'private 10/8' => ['https://10.1.2.3'],
            'private 192.168/16' => ['https://192.168.0.10:8443'],
            'cloud metadata' => ['https://169.254.169.254/latest'],
            'ipv6 loopback' => ['https://[::1]/'],
            'localhost' => ['https://localhost'],
            'no host' => ['not a url'],
        ];
    }

    #[DataProvider('internalUrls')]
    public function test_internal_hosts_are_detected(string $url): void
    {
        $this->assertTrue(PublicHost::isInternal($url));
    }

    public function test_public_ip_is_allowed(): void
    {
        $this->assertFalse(PublicHost::isInternal('https://8.8.8.8/'));
    }
}
