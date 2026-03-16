<?php

namespace CreativeCatCo\GkCmsCore\Services\Ai\Providers;

use CreativeCatCo\GkCmsCore\Services\Ai\AbstractLlmProvider;

class AnthropicProvider extends AbstractLlmProvider
{
    public function getName(): string
    {
        return 'anthropic';
    }

    protected function getBaseUrl(): string
    {
        return 'https://api.anthropic.com/v1';
    }

    protected function getChatEndpoint(): string
    {
        return '/messages';
    }

    protected function getAuthHeaders(): array
    {
        return [
            'x-api-key' => $this->apiKey,
            'anthropic-version' => '2023-06-01',
        ];
    }

    public function getAvailableModels(): array
    {
        return [
            'claude-sonnet-4-20250514' => 'Claude Sonnet 4 (Latest)',
            'claude-3-5-sonnet-20241022' => 'Claude 3.5 Sonnet',
            'claude-3-5-haiku-20241022' => 'Claude 3.5 Haiku (Fast)',
        ];
    }

    /**
     * Convert OpenAI-format messages to Anthropic format.
     * Anthropic uses a separate 'system' parameter and different tool result format.
     */
    protected function buildRequestBody(array $messages, array $tools = []): array
    {
        $systemPrompt = '';
        $anthropicMessages = [];

        foreach ($messages as $msg) {
            if ($msg['role'] === 'system') {
                $systemPrompt .= ($systemPrompt ? "\n\n" : '') . $msg['content'];
                continue;
            }

            if ($msg['role'] === 'tool') {
                // Anthropic uses 'tool_result' content blocks within 'user' role
                $anthropicMessages[] = [
                    'role' => 'user',
                    'content' => [
                        [
                            'type' => 'tool_result',
                            'tool_use_id' => $msg['tool_call_id'] ?? '',
                            'content' => $msg['content'] ?? '',
                        ],
                    ],
                ];
                continue;
            }

            if ($msg['role'] === 'assistant' && !empty($msg['tool_calls'])) {
                // Convert OpenAI tool_calls to Anthropic tool_use content blocks
                $content = [];
                if (!empty($msg['content'])) {
                    $content[] = ['type' => 'text', 'text' => $msg['content']];
                }
                foreach ($msg['tool_calls'] as $tc) {
                    $argsStr = $tc['function']['arguments'] ?? '';
                    $decoded = is_string($argsStr) ? json_decode($argsStr, true) : $argsStr;
                    // Anthropic requires 'input' to be a JSON object (dictionary), never an array.
                    // Empty or invalid args must become {} not []
                    if (!is_array($decoded) || empty($decoded) || array_is_list($decoded)) {
                        $decoded = (object)($decoded ?: []);
                    }
                    $content[] = [
                        'type' => 'tool_use',
                        'id' => $tc['id'],
                        'name' => $tc['function']['name'],
                        'input' => $decoded,
                    ];
                }
                $anthropicMessages[] = [
                    'role' => 'assistant',
                    'content' => $content,
                ];
                continue;
            }

            $anthropicMessages[] = [
                'role' => $msg['role'],
                'content' => $msg['content'] ?? '',
            ];
        }

        $body = [
            'model' => $this->model,
            'max_tokens' => $this->maxTokens,
            'temperature' => $this->temperature,
            'messages' => $anthropicMessages,
        ];

        if (!empty($systemPrompt)) {
            $body['system'] = $systemPrompt;
        }

        // Convert OpenAI tool format to Anthropic tool format
        if (!empty($tools)) {
            $anthropicTools = [];
            foreach ($tools as $tool) {
                if ($tool['type'] === 'function') {
                    $anthropicTools[] = [
                        'name' => $tool['function']['name'],
                        'description' => $tool['function']['description'] ?? '',
                        'input_schema' => $tool['function']['parameters'] ?? ['type' => 'object', 'properties' => []],
                    ];
                }
            }
            $body['tools'] = $anthropicTools;
        }

        return $body;
    }

    protected function parseStreamChunk(string $line, array &$state): ?array
    {
        // Anthropic SSE format: "event: ..." and "data: {...}"
        if (str_starts_with($line, 'event: ')) {
            $state['current_event'] = substr($line, 7);
            return null;
        }

        if (!str_starts_with($line, 'data: ')) {
            return null;
        }

        $data = json_decode(substr($line, 6), true);
        if ($data === null) return null;

        $event = $state['current_event'] ?? $data['type'] ?? '';

        switch ($event) {
            case 'content_block_start':
                $block = $data['content_block'] ?? [];
                if (($block['type'] ?? '') === 'tool_use') {
                    if (!isset($state['pending_tool_calls'])) {
                        $state['pending_tool_calls'] = [];
                    }
                    $state['current_tool_index'] = $data['index'] ?? count($state['pending_tool_calls']);
                    $state['pending_tool_calls'][$state['current_tool_index']] = [
                        'id' => $block['id'],
                        'type' => 'function',
                        'function' => [
                            'name' => $block['name'],
                            'arguments' => '',
                        ],
                    ];
                    // Emit early hint so the UI can show status immediately
                    return ['type' => 'tool_start_hint', 'data' => ['name' => $block['name']]];
                }
                return null;

            case 'content_block_delta':
                $delta = $data['delta'] ?? [];
                if (($delta['type'] ?? '') === 'text_delta') {
                    return ['type' => 'text', 'data' => $delta['text'] ?? ''];
                }
                if (($delta['type'] ?? '') === 'input_json_delta') {
                    $idx = $state['current_tool_index'] ?? 0;
                    if (isset($state['pending_tool_calls'][$idx])) {
                        $state['pending_tool_calls'][$idx]['function']['arguments'] .= $delta['partial_json'] ?? '';
                    }
                }
                return null;

            case 'message_delta':
                if (!empty($data['usage'])) {
                    $state['usage'] = $data['usage'];
                }
                return null;

            case 'message_stop':
                return ['type' => 'done', 'data' => null];

            case 'error':
                return ['type' => 'error', 'data' => $data['error']['message'] ?? 'Unknown error'];
        }

        return null;
    }

    protected function parseCompleteResponse(array $responseData): array
    {
        $content = '';
        $toolCalls = [];

        foreach ($responseData['content'] ?? [] as $block) {
            if ($block['type'] === 'text') {
                $content .= $block['text'];
            }
            if ($block['type'] === 'tool_use') {
                $toolCalls[] = [
                    'id' => $block['id'],
                    'type' => 'function',
                    'function' => [
                        'name' => $block['name'],
                        'arguments' => json_encode($block['input']),
                    ],
                ];
            }
        }

        return [
            'content' => $content,
            'tool_calls' => $toolCalls,
            'usage' => $responseData['usage'] ?? [],
        ];
    }

    /**
     * Validate API key for Anthropic (no /models endpoint, use a minimal messages call).
     */
    public function validateApiKey(): bool
    {
        try {
            $response = \Illuminate\Support\Facades\Http::withHeaders($this->getAuthHeaders())
                ->withHeaders(['Content-Type' => 'application/json'])
                ->timeout(10)
                ->post($this->getBaseUrl() . '/messages', [
                    'model' => $this->model ?: 'claude-3-5-haiku-20241022',
                    'max_tokens' => 1,
                    'messages' => [['role' => 'user', 'content' => 'Hi']],
                ]);

            return $response->successful();
        } catch (\Exception $e) {
            return false;
        }
    }
}
