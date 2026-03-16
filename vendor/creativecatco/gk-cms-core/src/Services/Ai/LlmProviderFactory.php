<?php

namespace CreativeCatCo\GkCmsCore\Services\Ai;

use CreativeCatCo\GkCmsCore\Models\Setting;
use CreativeCatCo\GkCmsCore\Services\Ai\Providers\OpenAiProvider;
use CreativeCatCo\GkCmsCore\Services\Ai\Providers\AnthropicProvider;
use CreativeCatCo\GkCmsCore\Services\Ai\Providers\GoogleProvider;
use CreativeCatCo\GkCmsCore\Services\Ai\Providers\XaiProvider;
use CreativeCatCo\GkCmsCore\Services\Ai\Providers\ManusProvider;

class LlmProviderFactory
{
    /**
     * Create an LLM provider instance from the current CMS settings.
     *
     * @throws \InvalidArgumentException if provider is not configured or unsupported
     */
    public static function create(?string $provider = null, ?string $apiKey = null, ?string $model = null): LlmProviderInterface
    {
        $provider = $provider ?? Setting::get('ai_provider', 'openai');
        $apiKey = $apiKey ?? Setting::get('ai_api_key', '');
        $model = $model ?? Setting::get('ai_model', '');
        $temperature = (float) Setting::get('ai_temperature', 0.7);
        $maxTokens = (int) Setting::get('ai_max_tokens', 16000);

        if (empty($apiKey)) {
            throw new \InvalidArgumentException(
                "No API key configured for AI provider '{$provider}'. Please add your API key in Settings > AI Assistant."
            );
        }

        return match ($provider) {
            'openai' => new OpenAiProvider($apiKey, $model ?: 'gpt-4.1-mini', $temperature, $maxTokens),
            'anthropic' => new AnthropicProvider($apiKey, $model ?: 'claude-sonnet-4-20250514', $temperature, $maxTokens),
            'google' => new GoogleProvider($apiKey, $model ?: 'gemini-2.5-flash', $temperature, $maxTokens),
            'xai' => new XaiProvider($apiKey, $model ?: 'grok-3-mini', $temperature, $maxTokens),
            'manus' => new ManusProvider($apiKey, $model ?: 'manus-1', $temperature, $maxTokens),
            default => throw new \InvalidArgumentException("Unsupported AI provider: {$provider}"),
        };
    }

    /**
     * Get the list of all supported providers with their display names.
     */
    public static function getProviders(): array
    {
        return [
            'openai' => 'OpenAI (GPT-4.1, GPT-4.1-mini)',
            'anthropic' => 'Anthropic (Claude)',
            'google' => 'Google (Gemini)',
            'xai' => 'xAI (Grok)',
            'manus' => 'Manus',
        ];
    }

    /**
     * Get available models for a specific provider.
     */
    public static function getModelsForProvider(string $provider): array
    {
        return match ($provider) {
            'openai' => (new OpenAiProvider('', ''))->getAvailableModels(),
            'anthropic' => (new AnthropicProvider('', ''))->getAvailableModels(),
            'google' => (new GoogleProvider('', ''))->getAvailableModels(),
            'xai' => (new XaiProvider('', ''))->getAvailableModels(),
            'manus' => (new ManusProvider('', ''))->getAvailableModels(),
            default => [],
        };
    }

    /**
     * Check if AI is configured (has provider and API key set).
     */
    public static function isConfigured(): bool
    {
        $provider = Setting::get('ai_provider', '');
        $apiKey = Setting::get('ai_api_key', '');

        return !empty($provider) && !empty($apiKey);
    }
}
