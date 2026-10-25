<?php

namespace Tests\Unit;

use App\Support\HttpErrorMail;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class HttpErrorMailTest extends TestCase
{
    /**
     * @return array<string, array{string, int|null}>
     */
    public static function subjects(): array
    {
        return [
            'error prefix' => ['[shop.example] Error 500 on /checkout', 500],
            'http prefix' => ['HTTP 503 – Dienst nicht erreichbar', 503],
            'http version' => ['HTTP/1.1 502 Bad Gateway', 502],
            'german prefix' => ['Fehler 404 auf der Startseite', 404],
            'status code' => ['Statuscode: 429', 429],
            'reason phrase' => ['404 Not Found: /impressum', 404],
            'gateway timeout' => ['Monitor: 504 Gateway Timeout', 504],
            'bare number' => ['Bestellung 404 ist angekommen', null],
            'outside range' => ['Error 599 aus dem Proxy', null],
            'unassigned 4xx' => ['Error 499', null],
            'success code' => ['HTTP 200 OK', null],
            'normal subject' => ['Drucker defekt', null],
        ];
    }

    #[DataProvider('subjects')]
    public function test_detects_http_status_from_subject(string $subject, ?int $expected): void
    {
        $this->assertSame($expected, HttpErrorMail::statusFromSubject($subject));
    }

    public function test_sender_list_is_normalised(): void
    {
        $this->assertSame(
            ['monitor@firma.de', 'shop.example'],
            HttpErrorMail::parseSenders("  Monitor@Firma.de \r\n@shop.example\n\nshop.example;"),
        );
    }
}
