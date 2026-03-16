<?php

namespace CreativeCatCo\GkCmsCore\Services\Ai\Providers;

use CreativeCatCo\GkCmsCore\Services\Ai\AbstractLlmProvider;

/**
 * Google Gemini provider using the OpenAI-compatible endpoint.
 * Google provides an OpenAI-compatible API at generativelanguage.googleapis.com.
 */
class GoogleProvider extends AbstractLlmProvider
{
    public function getName(): string
    {
        return 'google';
    }

    protected function getBaseUrl(): string
    {
        return 'https://generativelanguage.googleapis.com/v1beta/openai';
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
            'gemini-2.5-pro' => 'Gemini 2.5 Pro (Most capable)',
            'gemini-2.5-flash' => 'Gemini 2.5 Flash (Fast)',
            'gemini-2.0-flash' => 'Gemini 2.0 Flash',
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

    /**
     * Parse streaming chunks — uses OpenAI-compatible SSE format.
     */
    protected function parseStreamChunk(string $line, array &$state): ?array
    {
        if (!str_starts_with($line, 'data: ')) {
            return null;
        }

        $data = substr($line, 6);

        if ($data === '[DONE]') {
            return ['type' => 'done', 'data' => null];
        }

        $json = json_decode($data, true);
        if (!$json || empty($json['choices'])) {
            return null;
        }

        $choice = $json['choices'][0];
        $delta = $choice['delta'] ?? [];

        if (!empty($json['usage'])) {
            $state['usage'] = $json['usage'];
        }

        // Text content
        if (isset($delta['content']) && $delta['content'] !== '') {
            return ['type' => 'text', 'data' => $delta['content']];
        }

        // Tool calls
        if (isset($delta['tool_calls'])) {
            foreach ($delta['tool_calls'] as $tc) {
                $index = $tc['index'] ?? 0;

                if (!isset($state['pending_tool_calls'])) {
                    $state['pending_tool_calls'] = [];
                }

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
                    if (isset($state['pending_tool_calls'][$index])) {
                        $state['pending_tool_calls'][$index]['function']['arguments'] .= $tc['function']['arguments'] ?? '';
                    }
                }
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
