<?php

namespace App\Core\Ai;

use App\Core\Ai\Contracts\AiProviderInterface;
use App\Core\Ai\Providers\AnthropicProvider;
use App\Core\Ai\Providers\CustomEndpointProvider;
use App\Core\Ai\Providers\OllamaProvider;
use App\Core\Ai\Providers\OpenAiProvider;
use App\Models\AiSetting;
use App\Models\Team;
use RuntimeException;

/**
 * Resolves the configured AI provider for a team + use case, falling back
 * to .env-configured credentials when the team has no ai_settings row
 * (6.md: "API-Keys verschlüsselt in DB mit .env-Fallback").
 */
class AiProviderFactory
{
    public function make(Team $team, string $useCase): AiProviderInterface
    {
        $setting = AiSetting::query()
            ->where('team_id', $team->id)
            ->where('use_case', $useCase)
            ->first();

        $provider = $setting?->provider ?? config('services.default_ai_provider', 'openai');

        return match ($provider) {
            'openai' => new OpenAiProvider(
                apiKey: $setting?->api_key ?? config('services.openai.key'),
                model: $setting?->model ?? config('services.openai.model'),
            ),
            'anthropic' => new AnthropicProvider(
                apiKey: $setting?->api_key ?? config('services.anthropic.key'),
                model: $setting?->model ?? config('services.anthropic.model'),
            ),
            'ollama' => new OllamaProvider(
                endpoint: $setting?->endpoint_url ?? config('services.ollama.endpoint'),
                model: $setting?->model ?? config('services.ollama.model'),
            ),
            'custom' => new CustomEndpointProvider(
                endpoint: $setting?->endpoint_url ?? config('services.custom_ai.endpoint'),
                apiKey: $setting?->api_key ?? config('services.custom_ai.key'),
                model: $setting?->model ?? config('services.custom_ai.model'),
            ),
            default => throw new RuntimeException("Unknown AI provider [{$provider}]."),
        };
    }

    public function providerNameFor(Team $team, string $useCase): string
    {
        return AiSetting::query()
            ->where('team_id', $team->id)
            ->where('use_case', $useCase)
            ->value('provider') ?? config('services.default_ai_provider', 'openai');
    }

    public function redactPiiFor(Team $team, string $useCase): bool
    {
        return AiSetting::query()
            ->where('team_id', $team->id)
            ->where('use_case', $useCase)
            ->value('redact_pii') ?? false;
    }
}
