<?php

namespace CreativeCatCo\GkCmsCore\Services\Ai;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

abstract class AbstractLlmProvider implements LlmProviderInterface
{
    protected string $apiKey;
    protected string $model;
    protected float $temperature;
    protected int $maxTokens;

    public function __construct(string $apiKey, string $model, float $temperature = 0.7, int $maxTokens = 16000)
    {
        $this->apiKey = $apiKey;
        $this->model = $model;
        $this->temperature = $temperature;
        $this->maxTokens = $maxTokens;
    }

    /**
     * Get the base URL for the provider's API.
     */
    abstract protected function getBaseUrl(): string;

    /**
     * Get the authorization headers for the provider.
     */
    abstract protected function getAuthHeaders(): array;

    /**
     * Build the request body for a chat completion.
     */
    abstract protected function buildRequestBody(array $messages, array $tools = []): array;

    /**
     * Parse a streaming chunk from the provider's SSE response.
     * Returns: ['type' => 'text'|'tool_call'|'done'|'error', 'data' => mixed]
     */
    abstract protected function parseStreamChunk(string $line, array &$state): ?array;

    /**
     * Parse the final complete response into a normalized format.
     */
    abstract protected function parseCompleteResponse(array $responseData): array;

    /**
     * Send a streaming chat completion request.
     */
    public function chat(array $messages, array $tools = [], ?callable $onChunk = null): array
    {
        $body = $this->buildRequestBody($messages, $tools);
        $url = $this->getBaseUrl() . $this->getChatEndpoint();
        $headers = array_merge($this->getAuthHeaders(), [
            'Content-Type' => 'application/json',
            'Accept' => 'text/event-stream',
        ]);

        // If no streaming callback, do a non-streaming request
        if ($onChunk === null) {
            return $this->chatSync($url, $headers, $body);
        }

        // Streaming request
        return $this->chatStream($url, $headers, $body, $onChunk);
    }

    /**
     * Get the chat completions endpoint path.
     */
    protected function getChatEndpoint(): string
    {
        return '/chat/completions';
    }

    /**
     * Synchronous (non-streaming) chat request.
     */
    protected function chatSync(string $url, array $headers, array $body): array
    {
        // Ensure streaming is off for sync
        $body['stream'] = false;

        try {
            $response = Http::withHeaders($headers)
                ->timeout(120)
                ->post($url, $body);

            if (!$response->successful()) {
                $error = $response->json('error.message', $response->body());
                Log::error('LLM API error', ['provider' => $this->getName(), 'status' => $response->status(), 'error' => $error]);
                return [
                    'content' => '',
                    'tool_calls' => [],
                    'usage' => [],
                    'error' => $error,
                ];
            }

            return $this->parseCompleteResponse($response->json());
        } catch (\Exception $e) {
            Log::error('LLM API exception', ['provider' => $this->getName(), 'error' => $e->getMessage()]);
            return [
                'content' => '',
                'tool_calls' => [],
                'usage' => [],
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Streaming chat request using cURL for SSE support.
     */
    protected function chatStream(string $url, array $headers, array $body, callable $onChunk): array
    {
        // Track retry attempts internally but don't send to API
        $retryAttempt = $body['_retry_attempt'] ?? 0;
        unset($body['_retry_attempt']);
        $body['stream'] = true;

        $curlHeaders = [];
        foreach ($headers as $key => $value) {
            $curlHeaders[] = "$key: $value";
        }

        $fullContent = '';
        $toolCalls = [];
        $state = []; // Provider-specific streaming state
        $lineBuffer = ''; // Buffer for partial lines across CURL callbacks
        $rawResponseBody = ''; // Capture raw response for error debugging

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($body),
            CURLOPT_HTTPHEADER => $curlHeaders,
            CURLOPT_RETURNTRANSFER => false,
            CURLOPT_TIMEOUT => 300,
            CURLOPT_CONNECTTIMEOUT => 30,
            CURLOPT_WRITEFUNCTION => function ($ch, $data) use ($onChunk, &$fullContent, &$toolCalls, &$state, &$lineBuffer, &$rawResponseBody) {
                $originalLength = strlen($data);
                $rawResponseBody .= $data;

                // Prepend any leftover data from the previous callback
                $data = $lineBuffer . $data;
                $lineBuffer = '';

                $lines = explode("\n", $data);

                // If the data doesn't end with a newline, the last element is incomplete
                if (!str_ends_with($data, "\n")) {
                    $lineBuffer = array_pop($lines);
                }

                foreach ($lines as $line) {
                    $line = trim($line);
                    if (empty($line)) continue;

                    $parsed = $this->parseStreamChunk($line, $state);
                    if ($parsed === null) continue;

                    switch ($parsed['type']) {
                        case 'text':
                            $fullContent .= $parsed['data'];
                            $onChunk('text', $parsed['data']);
                            break;

                        case 'tool_call':
                            $toolCalls[] = $parsed['data'];
                            $onChunk('tool_call', $parsed['data']);
                            break;

                        case 'done':
                            // Finalize any accumulated tool calls from state
                            if (!empty($state['pending_tool_calls'])) {
                                foreach ($state['pending_tool_calls'] as $tc) {
                                    $argsStr = $tc['function']['arguments'] ?? '';
                                    Log::info('Finalizing tool call', [
                                        'tool' => $tc['function']['name'] ?? 'unknown',
                                        'args_length' => strlen($argsStr),
                                        'args_preview' => substr($argsStr, 0, 200),
                                        'args_end' => substr($argsStr, -100),
                                    ]);
                                    // Validate JSON is complete
                                    if (!empty($argsStr)) {
                                        $decoded = json_decode($argsStr, true);
                                        if ($decoded === null && json_last_error() !== JSON_ERROR_NONE) {
                                            Log::warning('Tool call has invalid/truncated JSON arguments', [
                                                'tool' => $tc['function']['name'] ?? 'unknown',
                                                'json_error' => json_last_error_msg(),
                                                'args_length' => strlen($argsStr),
                                            ]);
                                        }
                                    }
                                    $toolCalls[] = $tc;
                                    $onChunk('tool_call', $tc);
                                }
                            }
                            $onChunk('done', null);
                            break;

                        case 'tool_start_hint':
                            // Forward early tool start notification to the UI
                            $onChunk('tool_start_hint', $parsed['data']);
                            break;

                        case 'error':
                            $onChunk('error', $parsed['data']);
                            break;
                    }
                }
                return $originalLength;
            },
        ]);

        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        // Process any remaining data in the line buffer after CURL completes
        if (!empty($lineBuffer)) {
            $remainingLine = trim($lineBuffer);
            $lineBuffer = '';
            if (!empty($remainingLine)) {
                $parsed = $this->parseStreamChunk($remainingLine, $state);
                if ($parsed !== null) {
                    switch ($parsed['type']) {
                        case 'text':
                            $fullContent .= $parsed['data'];
                            $onChunk('text', $parsed['data']);
                            break;
                        case 'tool_call':
                            $toolCalls[] = $parsed['data'];
                            $onChunk('tool_call', $parsed['data']);
                            break;
                        case 'done':
                            if (!empty($state['pending_tool_calls'])) {
                                foreach ($state['pending_tool_calls'] as $tc) {
                                    $toolCalls[] = $tc;
                                    $onChunk('tool_call', $tc);
                                }
                            }
                            $onChunk('done', null);
                            break;
                        case 'error':
                            $onChunk('error', $parsed['data']);
                            break;
                    }
                }
            }
        }

        // Safety net: if we have pending tool calls that were never finalized
        // (e.g., message_stop event was lost or never received), finalize them now
        if (!empty($state['pending_tool_calls']) && empty($toolCalls)) {
            Log::warning('Finalizing orphaned pending tool calls after CURL completed', [
                'count' => count($state['pending_tool_calls']),
            ]);
            foreach ($state['pending_tool_calls'] as $tc) {
                $argsStr = $tc['function']['arguments'] ?? '';
                Log::info('Orphaned tool call', [
                    'tool' => $tc['function']['name'] ?? 'unknown',
                    'args_length' => strlen($argsStr),
                    'args_preview' => substr($argsStr, 0, 500),
                ]);
                $toolCalls[] = $tc;
                $onChunk('tool_call', $tc);
            }
            $onChunk('done', null);
        }

        if ($result === false || $httpCode >= 400) {
            // Try to extract the actual error message from the response body
            $errorDetail = '';
            if (!empty($rawResponseBody)) {
                $errorJson = json_decode($rawResponseBody, true);
                if ($errorJson) {
                    $errorDetail = $errorJson['error']['message'] ?? ($errorJson['message'] ?? '');
                } else {
                    $errorDetail = substr($rawResponseBody, 0, 1000);
                }
            }
            $error = $curlError ?: "HTTP $httpCode error";
            if ($errorDetail) {
                $error .= " — $errorDetail";
            }
            Log::error('LLM streaming error', [
                'provider' => $this->getName(),
                'http_code' => $httpCode,
                'error' => $error,
                'response_body_preview' => substr($rawResponseBody, 0, 2000),
                'request_message_count' => count($body['messages'] ?? []),
                'request_body_size' => strlen(json_encode($body)),
            ]);

            // Auto-retry on rate limit (429) with exponential backoff
            if ($httpCode === 429 && $retryAttempt < 3) {
                $waitSeconds = pow(2, $retryAttempt) * 15; // 15s, 30s, 60s
                Log::info('Rate limited, retrying', [
                    'attempt' => $retryAttempt + 1,
                    'wait_seconds' => $waitSeconds,
                ]);
                $onChunk('text', "\n\n*Rate limited by AI provider. Retrying in {$waitSeconds} seconds...*\n\n");
                sleep($waitSeconds);
                // Pass retry count via the body but it will be stripped at the top of chatStream
                $body['_retry_attempt'] = $retryAttempt + 1;
                unset($body['stream']); // chatStream will re-add this
                return $this->chatStream($url, $headers, $body, $onChunk);
            }

            $onChunk('error', $error);
        }

        return [
            'content' => $fullContent,
            'tool_calls' => $toolCalls,
            'usage' => $state['usage'] ?? [],
        ];
    }

    /**
     * Validate the API key by making a lightweight request.
     */
    public function validateApiKey(): bool
    {
        try {
            $response = Http::withHeaders($this->getAuthHeaders())
                ->timeout(10)
                ->get($this->getBaseUrl() . '/models');

            return $response->successful();
        } catch (\Exception $e) {
            return false;
        }
    }
}
