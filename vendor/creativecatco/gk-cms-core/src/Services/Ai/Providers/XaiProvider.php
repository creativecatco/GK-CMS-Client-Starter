<?php

namespace CreativeCatCo\GkCmsCore\Services\Ai\Providers;

/**
 * xAI (Grok) provider — fully OpenAI-compatible API.
 */
class XaiProvider extends OpenAiProvider
{
    public function __construct(string $apiKey, string $model, float $temperature = 0.7, int $maxTokens = 4096)
    {
        parent::__construct($apiKey, $model, $temperature, $maxTokens, 'https://api.x.ai/v1');
    }

    public function getName(): string
    {
        return 'xai';
    }

    public function getAvailableModels(): array
    {
        return [
            'grok-3' => 'Grok 3 (Most capable)',
            'grok-3-mini' => 'Grok 3 Mini (Fast)',
        ];
    }
}
