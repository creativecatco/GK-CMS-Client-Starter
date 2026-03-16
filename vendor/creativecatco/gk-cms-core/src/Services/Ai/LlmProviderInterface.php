<?php

namespace CreativeCatCo\GkCmsCore\Services\Ai;

interface LlmProviderInterface
{
    /**
     * Send a chat completion request with optional tool definitions.
     *
     * @param array $messages Array of message objects [{role, content, ...}]
     * @param array $tools Array of tool definitions in OpenAI function calling format
     * @param callable|null $onChunk Callback for streaming: fn(string $type, mixed $data)
     *   Types: 'text' (string chunk), 'tool_call' (array), 'done' (null), 'error' (string)
     * @return array The complete response: ['content' => string, 'tool_calls' => array, 'usage' => array]
     */
    public function chat(array $messages, array $tools = [], ?callable $onChunk = null): array;

    /**
     * Get the list of available models for this provider.
     *
     * @return array ['model_id' => 'Model Display Name', ...]
     */
    public function getAvailableModels(): array;

    /**
     * Get the provider name.
     */
    public function getName(): string;

    /**
     * Validate that the API key is configured and working.
     *
     * @return bool
     */
    public function validateApiKey(): bool;
}
