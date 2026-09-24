<?php

namespace App\Core\Ai\Providers;

use App\Core\Ai\Contracts\AiProviderInterface;
use Illuminate\Support\Facades\Http;

/**
 * OpenAI-compatible custom endpoint (self-hosted or third-party gateway),
 * for teams that don't want to use OpenAI/Anthropic/Ollama directly.
 */
class CustomEndpointProvider implements AiProviderInterface
{
    private int $lastUsageTokens = 0;

    public function __construct(
        private readonly string $endpoint,
        private readonly ?string $apiKey,
        private readonly string $model,
    ) {}

    public function summarize(string $text): string
    {
        return $this->chat('Fasse den folgenden Ticket-Verlauf präzise auf Deutsch zusammen:', $text);
    }

    public function suggestReply(string $conversationContext): string
    {
        return $this->chat('Formuliere einen hilfreichen, freundlichen Antwortvorschlag auf Deutsch für folgenden Ticket-Verlauf:', $conversationContext);
    }

    public function classify(string $text, array $labels): string
    {
        $labelList = implode(', ', $labels);

        return $this->chat("Ordne den folgenden Text genau einer dieser Kategorien zu ({$labelList}) und antworte nur mit dem Kategorienamen:", $text);
    }

    public function embed(string $text): array
    {
        $response = $this->client()
            ->post(rtrim($this->endpoint, '/').'/embeddings', [
                'model' => $this->model,
                'input' => $text,
            ])
            ->throw();

        $this->lastUsageTokens = (int) ($response->json('usage.total_tokens') ?? 0);

        return $response->json('data.0.embedding') ?? [];
    }

    public function lastUsageTokens(): int
    {
        return $this->lastUsageTokens;
    }

    private function chat(string $instruction, string $content): string
    {
        $response = $this->client()
            ->post(rtrim($this->endpoint, '/').'/chat/completions', [
                'model' => $this->model,
                'messages' => [
                    ['role' => 'system', 'content' => $instruction],
                    ['role' => 'user', 'content' => $content],
                ],
            ])
            ->throw();

        $this->lastUsageTokens = (int) ($response->json('usage.total_tokens') ?? 0);

        return $response->json('choices.0.message.content') ?? '';
    }

    private function client()
    {
        return $this->apiKey ? Http::withToken($this->apiKey) : Http::withHeaders([]);
    }
}
