<?php

namespace App\Core\Ai\Contracts;

interface AiProviderInterface
{
    public function summarize(string $text): string;

    public function suggestReply(string $conversationContext): string;

    /**
     * @param  array<int, string>  $labels
     */
    public function classify(string $text, array $labels): string;

    /**
     * @return array<int, float>
     */
    public function embed(string $text): array;

    /**
     * Token usage of the most recent call, for ai_usage_logs/cost control.
     * 0 when the provider doesn't report usage (e.g. local Ollama).
     */
    public function lastUsageTokens(): int;
}
