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
     * All supported providers with their display names and key prefixes.
     */
    protected static array $providerMeta = [
        'openai'    => ['name' => 'OpenAI', 'prefix' => 'sk-', 'exclude_prefix' => 'sk-ant-'],
        'anthropic' => ['name' => 'Anthropic', 'prefix' => 'sk-ant-', 'exclude_prefix' => null],
        'google'    => ['name' => 'Google Gemini', 'prefix' => 'AIza', 'exclude_prefix' => null],
        'xai'       => ['name' => 'xAI Grok', 'prefix' => 'xai-', 'exclude_prefix' => null],
        'manus'     => ['name' => 'Manus', 'prefix' => 'manus-', 'exclude_prefix' => null],
    ];

    /**
     * Create an LLM provider instance.
     *
     * @param string|null $provider Provider slug (e.g. 'openai', 'anthropic')
     * @param string|null $apiKey   API key override
     * @param string|null $model    Model ID override
     * @throws \InvalidArgumentException if provider is not configured or unsupported
     */
    public static function create(?string $provider = null, ?string $apiKey = null, ?string $model = null): LlmProviderInterface
    {
        $provider = $provider ?? Setting::get('ai_provider', 'openai');
        $model = $model ?? '';
        $temperature = (float) Setting::get('ai_temperature', 0.7);
        $maxTokens = (int) Setting::get('ai_max_tokens', 16000);

        // Resolve the API key for the requested provider
        if (empty($apiKey)) {
            $apiKey = self::resolveApiKeyForProvider($provider);
        }

        if (empty($apiKey)) {
            throw new \InvalidArgumentException(
                "No API key configured for AI provider '{$provider}'. Please add your API key in Settings > AI Assistant."
            );
        }

        return self::instantiateProvider($provider, $apiKey, $model, $temperature, $maxTokens);
    }

    /**
     * Resolve the API key for a given provider.
     *
     * Priority: dedicated provider key > main AI key (if it matches the provider)
     */
    public static function resolveApiKeyForProvider(string $provider): string
    {
        // Check for a dedicated key for this provider
        $dedicatedKey = Setting::get("ai_{$provider}_api_key", '');
        if (!empty($dedicatedKey)) {
            return $dedicatedKey;
        }

        // Use the main AI key if the current default provider matches
        $defaultProvider = Setting::get('ai_provider', 'openai');
        $mainKey = Setting::get('ai_api_key', '');

        if (!empty($mainKey) && $defaultProvider === $provider) {
            return $mainKey;
        }

        // Last resort: check if the main key looks like it belongs to this provider
        if (!empty($mainKey) && isset(self::$providerMeta[$provider])) {
            $meta = self::$providerMeta[$provider];
            if (str_starts_with($mainKey, $meta['prefix'])) {
                // Make sure it's not excluded (e.g., sk-ant- for OpenAI)
                if ($meta['exclude_prefix'] && str_starts_with($mainKey, $meta['exclude_prefix'])) {
                    return '';
                }
                return $mainKey;
            }
        }

        return '';
    }

    /**
     * Instantiate a provider by slug.
     */
    protected static function instantiateProvider(string $provider, string $apiKey, string $model, float $temperature, int $maxTokens): LlmProviderInterface
    {
        return match ($provider) {
            'openai'    => new OpenAiProvider($apiKey, $model ?: 'gpt-4.1-mini', $temperature, $maxTokens),
            'anthropic' => new AnthropicProvider($apiKey, $model ?: 'claude-sonnet-4-20250514', $temperature, $maxTokens),
            'google'    => new GoogleProvider($apiKey, $model ?: 'gemini-2.5-flash', $temperature, $maxTokens),
            'xai'       => new XaiProvider($apiKey, $model ?: 'grok-3-mini', $temperature, $maxTokens),
            'manus'     => new ManusProvider($apiKey, $model ?: 'manus-1', $temperature, $maxTokens),
            default     => throw new \InvalidArgumentException("Unsupported AI provider: {$provider}"),
        };
    }

    /**
     * Get the list of all supported providers with their display names.
     */
    public static function getProviders(): array
    {
        return [
            'openai'    => 'OpenAI (GPT-4.1, GPT-4.1-mini)',
            'anthropic' => 'Anthropic (Claude)',
            'google'    => 'Google (Gemini)',
            'xai'       => 'xAI (Grok)',
            'manus'     => 'Manus',
        ];
    }

    /**
     * Get available models for a specific provider.
     */
    public static function getModelsForProvider(string $provider): array
    {
        return match ($provider) {
            'openai'    => (new OpenAiProvider('', ''))->getAvailableModels(),
            'anthropic' => (new AnthropicProvider('', ''))->getAvailableModels(),
            'google'    => (new GoogleProvider('', ''))->getAvailableModels(),
            'xai'       => (new XaiProvider('', ''))->getAvailableModels(),
            'manus'     => (new ManusProvider('', ''))->getAvailableModels(),
            default     => [],
        };
    }

    /**
     * Get all providers that have a valid API key configured.
     * Returns an array of providers with their models, suitable for the chat dropdown.
     *
     * @return array [
     *   ['slug' => 'anthropic', 'name' => 'Anthropic', 'models' => [['id' => '...', 'name' => '...', 'default' => bool], ...], 'is_default' => bool],
     *   ...
     * ]
     */
    public static function getConfiguredProviders(): array
    {
        $defaultProvider = Setting::get('ai_provider', 'openai');
        $defaultModel = Setting::get('ai_model', '');
        $configured = [];

        foreach (self::getProviders() as $slug => $displayName) {
            $apiKey = self::resolveApiKeyForProvider($slug);
            if (empty($apiKey)) {
                continue;
            }

            $models = self::getModelsForProvider($slug);
            $modelList = [];
            foreach ($models as $modelId => $modelName) {
                $modelList[] = [
                    'id' => $modelId,
                    'name' => $modelName,
                    'default' => ($slug === $defaultProvider && $modelId === $defaultModel),
                ];
            }

            // Extract just the brand name for cleaner display
            $shortName = self::$providerMeta[$slug]['name'] ?? $displayName;

            $configured[] = [
                'slug' => $slug,
                'name' => $shortName,
                'models' => $modelList,
                'is_default' => ($slug === $defaultProvider),
            ];
        }

        return $configured;
    }

    /**
     * Check if AI is configured (has at least one provider with an API key).
     */
    public static function isConfigured(): bool
    {
        $provider = Setting::get('ai_provider', '');
        $apiKey = Setting::get('ai_api_key', '');

        return !empty($provider) && !empty($apiKey);
    }
}
