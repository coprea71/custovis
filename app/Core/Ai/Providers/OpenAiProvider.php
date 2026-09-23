<?php

namespace App\Core\Ai\Providers;

use App\Core\Ai\Contracts\AiProviderInterface;
use Illuminate\Support\Facades\Http;

class OpenAiProvider implements AiProviderInterface
{
    private int $lastUsageTokens = 0;

    public function __construct(
        private readonly string $apiKey,
        private readonly string $model = 'gpt-4o-mini',
        private readonly string $embeddingModel = 'text-embedding-3-small',
    ) {
    }

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
        $response = Http::withToken($this->apiKey)
            ->post('https://api.openai.com/v1/embeddings', [
                'model' => $this->embeddingModel,
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
        $response = Http::withToken($this->apiKey)
            ->post('https://api.openai.com/v1/chat/completions', [
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
}
