<?php

namespace App\Core\Ai\Providers;

use App\Core\Ai\Contracts\AiProviderInterface;
use Illuminate\Support\Facades\Http;

class AnthropicProvider implements AiProviderInterface
{
    private int $lastUsageTokens = 0;

    public function __construct(
        private readonly string $apiKey,
        private readonly string $model = 'claude-haiku-4-5-20251001',
    ) {}

    public function summarize(string $text): string
    {
        return $this->message('Fasse den folgenden Ticket-Verlauf präzise auf Deutsch zusammen:', $text);
    }

    public function suggestReply(string $conversationContext): string
    {
        return $this->message('Formuliere einen hilfreichen, freundlichen Antwortvorschlag auf Deutsch für folgenden Ticket-Verlauf:', $conversationContext);
    }

    public function classify(string $text, array $labels): string
    {
        $labelList = implode(', ', $labels);

        return $this->message("Ordne den folgenden Text genau einer dieser Kategorien zu ({$labelList}) und antworte nur mit dem Kategorienamen:", $text);
    }

    public function embed(string $text): array
    {
        // Anthropic offers no first-party embeddings API; callers needing
        // embeddings should configure a different provider for that use case.
        throw new \RuntimeException('AnthropicProvider does not support embeddings.');
    }

    public function lastUsageTokens(): int
    {
        return $this->lastUsageTokens;
    }

    private function message(string $instruction, string $content): string
    {
        $response = Http::withHeaders([
            'x-api-key' => $this->apiKey,
            'anthropic-version' => '2023-06-01',
        ])->post('https://api.anthropic.com/v1/messages', [
            'model' => $this->model,
            'max_tokens' => 1024,
            'system' => $instruction,
            'messages' => [
                ['role' => 'user', 'content' => $content],
            ],
        ])->throw();

        $this->lastUsageTokens = (int) ($response->json('usage.input_tokens') ?? 0)
            + (int) ($response->json('usage.output_tokens') ?? 0);

        return $response->json('content.0.text') ?? '';
    }
}
