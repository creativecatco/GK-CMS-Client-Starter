<?php

namespace CreativeCatCo\GkCmsCore\Services\Ai\Providers;

/**
 * Manus provider — OpenAI-compatible API.
 */
class ManusProvider extends OpenAiProvider
{
    public function __construct(string $apiKey, string $model, float $temperature = 0.7, int $maxTokens = 4096)
    {
        parent::__construct($apiKey, $model, $temperature, $maxTokens, 'https://api.manus.im/v1');
    }

    public function getName(): string
    {
        return 'manus';
    }

    public function getAvailableModels(): array
    {
        return [
            'manus-1' => 'Manus 1',
        ];
    }
}
