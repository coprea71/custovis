<?php

namespace Tests\Support;

use App\Core\Ai\Contracts\AiProviderInterface;

class FakeAiProvider implements AiProviderInterface
{
    public static array $calls = [];

    public function summarize(string $text): string
    {
        self::$calls[] = ['summarize', $text];

        return 'Zusammenfassung: '.substr($text, 0, 20);
    }

    public function suggestReply(string $conversationContext): string
    {
        self::$calls[] = ['suggestReply', $conversationContext];

        return 'Vorschlag: Danke für Ihre Nachricht.';
    }

    public function classify(string $text, array $labels): string
    {
        self::$calls[] = ['classify', $text];

        return $labels[0] ?? '';
    }

    public function embed(string $text): array
    {
        self::$calls[] = ['embed', $text];

        return [1.0, 0.0, 0.0];
    }

    public function lastUsageTokens(): int
    {
        return 42;
    }
}
