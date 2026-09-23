<?php

namespace App\Core\Ai\Providers;

use App\Core\Ai\Contracts\AiProviderInterface;
use Illuminate\Support\Facades\Http;

/**
 * Local/free provider (no API key, no per-token cost) — the "no
 * Nachkauf-Kosten" option from 0.md.
 */
class OllamaProvider implements AiProviderInterface
{
    public function __construct(
        private readonly string $endpoint,
        private readonly string $model = 'llama3',
        private readonly string $embeddingModel = 'nomic-embed-text',
    ) {
    }

    public function summarize(string $text): string
    {
        return $this->generate('Fasse den folgenden Ticket-Verlauf präzise auf Deutsch zusammen:'."\n\n".$text);
    }

    public function suggestReply(string $conversationContext): string
    {
        return $this->generate('Formuliere einen hilfreichen, freundlichen Antwortvorschlag auf Deutsch für folgenden Ticket-Verlauf:'."\n\n".$conversationContext);
    }

    public function classify(string $text, array $labels): string
    {
        $labelList = implode(', ', $labels);

        return $this->generate("Ordne den folgenden Text genau einer dieser Kategorien zu ({$labelList}) und antworte nur mit dem Kategorienamen:\n\n{$text}");
    }

    public function embed(string $text): array
    {
        $response = Http::post(rtrim($this->endpoint, '/').'/api/embeddings', [
            'model' => $this->embeddingModel,
            'prompt' => $text,
        ])->throw();

        return $response->json('embedding') ?? [];
    }

    public function lastUsageTokens(): int
    {
        return 0; // Local inference — no per-token cost to track.
    }

    private function generate(string $prompt): string
    {
        $response = Http::post(rtrim($this->endpoint, '/').'/api/generate', [
            'model' => $this->model,
            'prompt' => $prompt,
            'stream' => false,
        ])->throw();

        return $response->json('response') ?? '';
    }
}
