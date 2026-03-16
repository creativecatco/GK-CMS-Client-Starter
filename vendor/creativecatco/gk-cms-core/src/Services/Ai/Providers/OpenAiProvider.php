<?php

namespace CreativeCatCo\GkCmsCore\Services\Ai\Providers;

use CreativeCatCo\GkCmsCore\Services\Ai\AbstractLlmProvider;

class OpenAiProvider extends AbstractLlmProvider
{
    protected string $baseUrl;

    public function __construct(string $apiKey, string $model, float $temperature = 0.7, int $maxTokens = 4096, ?string $baseUrl = null)
    {
        parent::__construct($apiKey, $model, $temperature, $maxTokens);
        $this->baseUrl = $baseUrl ?? 'https://api.openai.com/v1';
    }

    public function getName(): string
    {
        return 'openai';
    }

    protected function getBaseUrl(): string
    {
        return $this->baseUrl;
    }

    protected function getAuthHeaders(): array
    {
        return [
            'Authorization' => 'Bearer ' . $this->apiKey,
        ];
    }

    public function getAvailableModels(): array
    {
        return [
            'gpt-4.1' => 'GPT-4.1 (Most capable)',
            'gpt-4.1-mini' => 'GPT-4.1 Mini (Fast & affordable)',
            'gpt-4.1-nano' => 'GPT-4.1 Nano (Fastest)',
            'gpt-4o' => 'GPT-4o',
            'gpt-4o-mini' => 'GPT-4o Mini',
        ];
    }

    protected function buildRequestBody(array $messages, array $tools = []): array
    {
        $body = [
            'model' => $this->model,
            'messages' => $messages,
            'temperature' => $this->temperature,
            'max_tokens' => $this->maxTokens,
        ];

        if (!empty($tools)) {
            $body['tools'] = $tools;
            $body['tool_choice'] = 'auto';
        }

        return $body;
    }

    protected function parseStreamChunk(string $line, array &$state): ?array
    {
        // SSE format: "data: {...}" or "data: [DONE]"
        if (!str_starts_with($line, 'data: ')) {
            return null;
        }

        $data = substr($line, 6); // Remove "data: " prefix

        if ($data === '[DONE]') {
            return ['type' => 'done', 'data' => null];
        }

        $json = json_decode($data, true);
        if (!$json || empty($json['choices'])) {
            return null;
        }

        $choice = $json['choices'][0];
        $delta = $choice['delta'] ?? [];

        // Track usage if provided
        if (!empty($json['usage'])) {
            $state['usage'] = $json['usage'];
        }

        // Text content
        if (isset($delta['content']) && $delta['content'] !== '') {
            return ['type' => 'text', 'data' => $delta['content']];
        }

        // Tool calls (streamed incrementally)
        if (isset($delta['tool_calls'])) {
            foreach ($delta['tool_calls'] as $tc) {
                $index = $tc['index'] ?? 0;

                if (!isset($state['pending_tool_calls'])) {
                    $state['pending_tool_calls'] = [];
                }

                // New tool call
                if (isset($tc['id'])) {
                    $state['pending_tool_calls'][$index] = [
                        'id' => $tc['id'],
                        'type' => 'function',
                        'function' => [
                            'name' => $tc['function']['name'] ?? '',
                            'arguments' => $tc['function']['arguments'] ?? '',
                        ],
                    ];
                } else {
                    // Append to existing tool call arguments
                    if (isset($state['pending_tool_calls'][$index])) {
                        $state['pending_tool_calls'][$index]['function']['arguments'] .= $tc['function']['arguments'] ?? '';
                    }
                }
            }
        }

        // Finish reason
        if (isset($choice['finish_reason']) && $choice['finish_reason'] !== null) {
            if ($choice['finish_reason'] === 'tool_calls' || $choice['finish_reason'] === 'stop') {
                // Tool calls will be finalized in the 'done' handler
            }
        }

        return null;
    }

    protected function parseCompleteResponse(array $responseData): array
    {
        $choice = $responseData['choices'][0] ?? [];
        $message = $choice['message'] ?? [];

        $toolCalls = [];
        if (!empty($message['tool_calls'])) {
            foreach ($message['tool_calls'] as $tc) {
                $toolCalls[] = [
                    'id' => $tc['id'],
                    'type' => 'function',
                    'function' => [
                        'name' => $tc['function']['name'],
                        'arguments' => $tc['function']['arguments'],
                    ],
                ];
            }
        }

        return [
            'content' => $message['content'] ?? '',
            'tool_calls' => $toolCalls,
            'usage' => $responseData['usage'] ?? [],
        ];
    }
}
