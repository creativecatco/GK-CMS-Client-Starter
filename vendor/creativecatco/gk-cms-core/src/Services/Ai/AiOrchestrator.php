<?php

namespace CreativeCatCo\GkCmsCore\Services\Ai;

use CreativeCatCo\GkCmsCore\Models\AiConversation;
use CreativeCatCo\GkCmsCore\Services\Ai\Tools\ToolExecutor;
use CreativeCatCo\GkCmsCore\Services\Ai\Tools\ToolRegistry;
use Illuminate\Support\Facades\Log;

class AiOrchestrator
{
    protected LlmProviderInterface $provider;
    protected ToolExecutor $toolExecutor;
    protected ToolRegistry $toolRegistry;
    protected SystemPromptLoader $promptLoader;

    /**
     * Maximum number of tool-call loops before forcing a text response.
     * Prevents infinite loops if the LLM keeps calling tools.
     */
    protected int $maxToolLoops = 12;

    /**
     * Track tool calls across the entire agent loop to detect repetition.
     * Each entry: ['name' => string, 'params_hash' => string]
     */
    protected array $toolCallHistory = [];

    public function __construct(
        LlmProviderInterface $provider,
        ToolExecutor $toolExecutor,
        ToolRegistry $toolRegistry,
        SystemPromptLoader $promptLoader
    ) {
        $this->provider = $provider;
        $this->toolExecutor = $toolExecutor;
        $this->toolRegistry = $toolRegistry;
        $this->promptLoader = $promptLoader;
    }

    /**
     * Create an orchestrator from the current CMS settings.
     *
     * @param int|null $userId User ID for preference loading
     * @param string|null $providerSlug Override the default LLM provider (e.g. 'openai', 'anthropic')
     * @param string|null $modelId Override the default model for the provider
     */
    public static function create(?int $userId = null, ?string $providerSlug = null, ?string $modelId = null): self
    {
        $provider = LlmProviderFactory::create($providerSlug, null, $modelId);
        $registry = ToolRegistry::createDefault();
        $executor = new ToolExecutor($registry);
        $promptLoader = new SystemPromptLoader();

        // Inject user ID for preference loading (conversation memory)
        if ($userId) {
            $promptLoader->forUser($userId);
        }

        return new self($provider, $executor, $registry, $promptLoader);
    }

    /**
     * Handle a user message with streaming output.
     *
     * This is the main entry point. It:
     * 1. Adds the user message to the conversation
     * 2. Builds the full message array (system + history + user)
     * 3. Calls the LLM with tool definitions
     * 4. If the LLM returns tool calls, executes them and loops
     * 5. Streams text responses to the callback
     *
     * @param AiConversation $conversation The conversation to continue
     * @param string $userMessage The user's new message
     * @param callable $onEvent Callback: fn(string $type, mixed $data)
     *   Types:
     *   - 'text': string chunk of AI response
     *   - 'tool_start': array {name, params} — tool is about to execute
     *   - 'tool_result': array {name, result} — tool finished
     *   - 'thinking': string — AI is processing (status message)
     *   - 'done': null — response complete
     *   - 'error': string — error message
     */
    public function handleMessage(AiConversation $conversation, string $userMessage, callable $onEvent): void
    {
        try {
            // Reset tool call history for this new user message
            $this->toolCallHistory = [];

            // 1. Add user message to conversation
            $conversation->addMessage('user', $userMessage);
            $conversation->generateTitle();

            // 2. Build the messages array
            $systemPrompt = $this->promptLoader->load();
            $messages = $this->buildMessages($systemPrompt, $conversation);

            // 3. Get tool definitions (use lightweight set to reduce token usage)
            // Heavy/rarely-used tools are excluded but still available for execution
            // if the AI somehow references them from conversation context
            $tools = $this->toolRegistry->getLightweightToolDefinitions();

            // 4. Run the agentic loop
            $this->agentLoop($conversation, $messages, $tools, $onEvent);

        } catch (\Exception $e) {
            Log::error('AI Orchestrator error', [
                'conversation_id' => $conversation->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            $onEvent('error', 'An error occurred: ' . $e->getMessage());
        }
    }

    /**
     * The agentic loop: call LLM → execute tools → call LLM again → repeat.
     */
    protected function agentLoop(
        AiConversation $conversation,
        array $messages,
        array $tools,
        callable $onEvent,
        int $loopCount = 0
    ): void {
        if ($loopCount >= $this->maxToolLoops) {
            $onEvent('error', 'Maximum tool call limit reached. Please try a simpler request.');
            return;
        }

        // Call the LLM with streaming
        $fullContent = '';
        $toolCallsReceived = [];

        $response = $this->provider->chat($messages, $tools, function ($type, $data) use ($onEvent, &$fullContent, &$toolCallsReceived) {
            switch ($type) {
                case 'text':
                    $fullContent .= $data;
                    $onEvent('text', $data);
                    break;

                case 'tool_call':
                    $toolCallsReceived[] = $data;
                    break;

                case 'tool_start_hint':
                    // Early notification that a tool call is being generated
                    $onEvent('tool_start_hint', $data);
                    break;

                case 'done':
                    // Don't emit done yet — we may need to process tool calls
                    break;

                case 'error':
                    $onEvent('error', $data);
                    break;
            }
        });

        // If there was an error in the response
        if (!empty($response['error'])) {
            // Save any partial content that was streamed before the error
            if (!empty($fullContent)) {
                $conversation->addMessage('assistant', $fullContent);
            }
            $onEvent('error', $response['error']);
            return;
        }

        // Merge any tool calls from the response object (non-streaming providers)
        if (!empty($response['tool_calls'])) {
            $toolCallsReceived = $response['tool_calls'];
        }

        // Use full content from response if streaming didn't capture it
        if (empty($fullContent) && !empty($response['content'])) {
            $fullContent = $response['content'];
            $onEvent('text', $fullContent);
        }

        // ── Loop prevention: detect if the LLM is restarting a completed task ──
        // If the LLM generated substantial text AND tool calls, check if the text
        // contains completion language followed by a new task start. This indicates
        // the LLM is "role-playing" both sides of the conversation.
        if (!empty($toolCallsReceived) && !empty($fullContent)) {
            if ($this->detectCompletionLoop($fullContent, $loopCount)) {
                Log::warning('AI loop prevention: detected completion-then-restart pattern', [
                    'loop_count' => $loopCount,
                    'content_preview' => substr($fullContent, 0, 300),
                    'tool_calls' => array_map(fn($tc) => $tc['function']['name'] ?? 'unknown', $toolCallsReceived),
                ]);

                // Save the text portion only, discard the tool calls
                $conversation->addMessage('assistant', $fullContent);
                $onEvent('done', null);
                return;
            }
        }

        // If the LLM returned tool calls, execute them and loop
        if (!empty($toolCallsReceived)) {
            // ── Loop prevention: detect duplicate tool calls ──
            $toolCallsReceived = $this->filterDuplicateToolCalls($toolCallsReceived, $loopCount);

            if (empty($toolCallsReceived)) {
                // All tool calls were duplicates — treat as final response
                Log::warning('AI loop prevention: all tool calls were duplicates, stopping', [
                    'loop_count' => $loopCount,
                ]);
                if (!empty($fullContent)) {
                    $conversation->addMessage('assistant', $fullContent);
                }
                $onEvent('done', null);
                return;
            }

            // Save the assistant message with tool calls
            $conversation->addMessage('assistant', $fullContent, $toolCallsReceived);

            // Truncate large tool call arguments to reduce token usage in subsequent calls
            // The full arguments are saved to the conversation DB, but the LLM doesn't need them again
            $truncatedToolCalls = array_map(function ($tc) {
                $args = $tc['function']['arguments'] ?? '';
                if (is_string($args) && strlen($args) > 2000) {
                    $tc['function']['arguments'] = json_encode([
                        '_truncated' => true,
                        '_summary' => 'Arguments were ' . strlen($args) . ' chars. Tool was executed with full arguments.',
                    ]);
                }
                return $tc;
            }, $toolCallsReceived);

            // Add the assistant message to the messages array for the next call
            $assistantMessage = [
                'role' => 'assistant',
                'content' => $fullContent ?: null,
                'tool_calls' => $truncatedToolCalls,
            ];
            $messages[] = $assistantMessage;

            // Execute each tool call
            foreach ($toolCallsReceived as $toolCall) {
                $toolName = $toolCall['function']['name'] ?? 'unknown';
                $toolArgs = $toolCall['function']['arguments'] ?? '{}';
                $toolCallId = $toolCall['id'] ?? '';

                // Parse arguments
                $params = is_string($toolArgs) ? (json_decode($toolArgs, true) ?? []) : $toolArgs;

                // Log the raw arguments for debugging
                Log::info('AI Tool Call', [
                    'tool' => $toolName,
                    'raw_args_type' => gettype($toolArgs),
                    'raw_args_length' => is_string($toolArgs) ? strlen($toolArgs) : 'N/A',
                    'raw_args_preview' => is_string($toolArgs) ? substr($toolArgs, 0, 200) : json_encode($toolArgs),
                    'parsed_params_keys' => array_keys($params),
                    'json_last_error' => json_last_error_msg(),
                ]);

                // Notify UI that a tool is starting
                $onEvent('tool_start', [
                    'name' => $toolName,
                    'params' => $params,
                    'tool_call_id' => $toolCallId,
                ]);

                // Execute the tool with error recovery
                try {
                    $result = $this->toolExecutor->execute(
                        $toolName,
                        $params,
                        $conversation,
                        $conversation->user_id
                    );
                } catch (\Exception $e) {
                    Log::error('AI Tool execution error', [
                        'tool' => $toolName,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);

                    // Return the error as a tool result so the AI can reason about it
                    $result = [
                        'success' => false,
                        'error' => 'Tool execution failed: ' . $e->getMessage(),
                        'hint' => 'Check read_error_log for more details. Try a different approach if this tool keeps failing.',
                    ];
                }

                // Notify UI of the result
                $onEvent('tool_result', [
                    'name' => $toolName,
                    'result' => $result,
                    'tool_call_id' => $toolCallId,
                ]);

                // Add tool result to conversation
                $toolResultContent = json_encode($result);
                $conversation->addMessage('tool', $toolResultContent, null, [
                    'tool_call_id' => $toolCallId,
                    'name' => $toolName,
                ]);

                // Add tool result to messages for the next LLM call
                // Smart truncation: preserve critical data, trim verbose parts
                $llmResultContent = $toolResultContent;
                $maxResultSize = 12000; // Allow larger results for data-rich tools (pages can have 35+ fields)

                if (strlen($llmResultContent) > $maxResultSize) {
                    $llmResultContent = $this->smartTruncateToolResult($toolName, $result, $maxResultSize);
                }
                $messages[] = [
                    'role' => 'tool',
                    'tool_call_id' => $toolCallId,
                    'name' => $toolName,
                    'content' => $llmResultContent,
                ];
            }

            // Notify UI that we're continuing the loop with context
            $toolNames = array_map(fn($tc) => $tc['function']['name'] ?? 'unknown', $toolCallsReceived);
            $onEvent('thinking', 'Analyzing results from ' . implode(', ', $toolNames) . '...');

            // Recurse: call the LLM again with the tool results
            $this->agentLoop($conversation, $messages, $tools, $onEvent, $loopCount + 1);

        } else {
            // No tool calls — this is the final text response
            if (!empty($fullContent)) {
                $conversation->addMessage('assistant', $fullContent);
            }
            $onEvent('done', null);
        }
    }

    /**
     * Detect if the LLM's text response contains a completion followed by
     * starting a new task (the "completion loop" pattern).
     *
     * Examples of this pattern:
     * - "...The changes are now live.Of course. Let me generate a better image..."
     * - "...Is there anything else?Of course. First, I need to..."
     */
    protected function detectCompletionLoop(string $content, int $loopCount): bool
    {
        // Only check after at least 2 loops (the LLM has already done some work)
        if ($loopCount < 2) {
            return false;
        }

        $content = strtolower($content);

        // Completion phrases that indicate the task is done
        $completionPhrases = [
            'the changes are now live',
            'has been updated',
            'has been successfully updated',
            'is now live',
            'changes are live',
            'anything else i can help',
            'anything else you',
            'let me know if you',
            'is there anything else',
            'the task is complete',
            'all done',
            'everything is set',
        ];

        // Restart phrases that indicate a new task is beginning
        $restartPhrases = [
            'of course.',
            'of course!',
            'sure!',
            'sure,',
            'absolutely.',
            'absolutely!',
            'let me start',
            'i\'ll start by',
            'first, i need to',
            'let me check',
            'let me examine',
            'i\'ll begin by',
            'to generate a',
            'to create a',
        ];

        $hasCompletion = false;
        $completionPos = 0;
        foreach ($completionPhrases as $phrase) {
            $pos = strpos($content, $phrase);
            if ($pos !== false) {
                $hasCompletion = true;
                $completionPos = max($completionPos, $pos + strlen($phrase));
            }
        }

        if (!$hasCompletion) {
            return false;
        }

        // Check if any restart phrase appears AFTER the completion
        $afterCompletion = substr($content, $completionPos);
        foreach ($restartPhrases as $phrase) {
            if (str_contains($afterCompletion, $phrase)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Filter out duplicate tool calls that have already been executed
     * in this agent loop session with the same or very similar parameters.
     */
    protected function filterDuplicateToolCalls(array $toolCalls, int $loopCount): array
    {
        $filtered = [];

        foreach ($toolCalls as $tc) {
            $name = $tc['function']['name'] ?? 'unknown';
            $args = $tc['function']['arguments'] ?? '{}';
            $paramsHash = md5($name . ':' . $args);

            // Check for exact duplicates
            $isDuplicate = false;
            foreach ($this->toolCallHistory as $prev) {
                if ($prev['name'] === $name && $prev['params_hash'] === $paramsHash) {
                    $isDuplicate = true;
                    Log::warning('AI loop prevention: skipping duplicate tool call', [
                        'tool' => $name,
                        'loop_count' => $loopCount,
                    ]);
                    break;
                }
            }

            // For generate_image specifically, also check for similar calls
            // (same tool called multiple times with different prompts for the same purpose)
            if (!$isDuplicate && $name === 'generate_image' && $loopCount >= 3) {
                $imageCallCount = count(array_filter($this->toolCallHistory, fn($h) => $h['name'] === 'generate_image'));
                if ($imageCallCount >= 2) {
                    $isDuplicate = true;
                    Log::warning('AI loop prevention: too many generate_image calls, stopping', [
                        'previous_count' => $imageCallCount,
                        'loop_count' => $loopCount,
                    ]);
                }
            }

            if (!$isDuplicate) {
                $this->toolCallHistory[] = [
                    'name' => $name,
                    'params_hash' => $paramsHash,
                ];
                $filtered[] = $tc;
            }
        }

        return $filtered;
    }

    /**
     * Build the full messages array for the LLM.
     */
    protected function buildMessages(string $systemPrompt, AiConversation $conversation): array
    {
        $messages = [];

        // System prompt
        $messages[] = [
            'role' => 'system',
            'content' => $systemPrompt,
        ];

        // Conversation history
        $history = $conversation->getMessagesForApi();

        // Token management: if history is very long, summarize older messages
        // For now, include all messages but limit to last 50 exchanges
        $maxMessages = 100; // ~50 exchanges (user + assistant)
        if (count($history) > $maxMessages) {
            // Keep the first few messages for context, then the most recent ones
            $keepFirst = 4; // First 2 exchanges
            $keepRecent = $maxMessages - $keepFirst - 1; // -1 for summary message

            $firstMessages = array_slice($history, 0, $keepFirst);
            $recentMessages = array_slice($history, -$keepRecent);

            $summary = [
                'role' => 'system',
                'content' => '[Note: Earlier conversation messages have been summarized to fit within context limits. The most recent messages are preserved in full.]',
            ];

            $messages = array_merge($messages, $firstMessages, [$summary], $recentMessages);
        } else {
            $messages = array_merge($messages, $history);
        }

        return $messages;
    }

    /**
     * Get the tool executor (for external access to rollback, etc.)
     */
    public function getToolExecutor(): ToolExecutor
    {
        return $this->toolExecutor;
    }

    /**
     * Get the tool registry.
     */
    public function getToolRegistry(): ToolRegistry
    {
        return $this->toolRegistry;
    }

    /**
     * Smart truncation of tool results.
     * Preserves critical data (field_map, message, usage_hint) while trimming verbose parts.
     */
    protected function smartTruncateToolResult(string $toolName, array $result, int $maxSize): string
    {
        $data = $result['data'] ?? [];

        // For get_page_info: preserve field_map (has type info), trim template
        if ($toolName === 'get_page_info' && is_array($data)) {
            // Truncate the template which is usually the largest part
            if (isset($data['template']) && is_string($data['template']) && strlen($data['template']) > 3000) {
                $data['template'] = mb_substr($data['template'], 0, 3000) . '\n... [template truncated — for small fixes use patch_page_template with find/replace instead of replacing the full template]';
            }
            if (isset($data['custom_template']) && is_string($data['custom_template']) && strlen($data['custom_template']) > 3000) {
                $data['custom_template'] = mb_substr($data['custom_template'], 0, 3000) . '\n... [template truncated — for small fixes use patch_page_template with find/replace instead of replacing the full template]';
            }
            // Remove field_definitions if field_map is present (field_map already includes type info)
            if (isset($data['field_map']) && isset($data['field_definitions'])) {
                unset($data['field_definitions']);
            }
            // Remove raw fields if field_map is present (field_map already includes values)
            if (isset($data['field_map']) && isset($data['fields'])) {
                unset($data['fields']);
            }

            $truncated = $result;
            $truncated['data'] = $data;
            $truncated['_note'] = 'Template truncated to save tokens. field_map contains all field types and values. For small template fixes, use patch_page_template (find/replace) instead of update_page_template (full replacement).';
            $encoded = json_encode($truncated);

            if (strlen($encoded) <= $maxSize) {
                return $encoded;
            }

            // Still too large — progressively truncate field_map values
            // Step 1: Truncate long string values and summarize arrays in field_map
            if (isset($data['field_map']) && is_array($data['field_map'])) {
                foreach ($data['field_map'] as $key => &$entry) {
                    if (!isset($entry['value'])) continue;
                    $val = $entry['value'];
                    if (is_array($val)) {
                        // Summarize arrays (repeaters, button groups)
                        if (isset($val['image'])) {
                            // section_bg — keep it (important for image tasks)
                            continue;
                        }
                        $entry['value'] = '[array with ' . count($val) . ' items]';
                    } elseif (is_string($val) && strlen($val) > 150) {
                        $entry['value'] = mb_substr($val, 0, 150) . '...';
                    }
                }
                unset($entry);
            }

            // Step 2: Further truncate template if needed
            if (isset($data['custom_template']) && is_string($data['custom_template']) && strlen($data['custom_template']) > 1500) {
                $data['custom_template'] = mb_substr($data['custom_template'], 0, 1500) . '\n... [template further truncated]';
            }

            // Step 3: Remove non-essential metadata
            unset($data['custom_css'], $data['featured_image'], $data['sort_order']);

            $truncated['data'] = $data;
            $encoded = json_encode($truncated);

            if (strlen($encoded) <= $maxSize) {
                return $encoded;
            }

            // Step 4: Last resort — keep only field_map with types and truncated values
            $minimalData = [
                'id' => $data['id'] ?? null,
                'title' => $data['title'] ?? null,
                'slug' => $data['slug'] ?? null,
                'page_type' => $data['page_type'] ?? 'page',
                'field_map' => $data['field_map'] ?? [],
            ];
            return json_encode([
                'success' => $result['success'] ?? true,
                'message' => $result['message'] ?? 'Page retrieved.',
                'data' => $minimalData,
                '_note' => 'Result heavily truncated to fit token limit. Template omitted — use get_page_info again or patch_page_template for template changes.',
            ]);
        }

        // For render_page: preserve sections and issues, trim raw template
        if ($toolName === 'render_page' && is_array($data)) {
            if (isset($data['raw_template_preview'])) {
                $data['raw_template_preview'] = '[template omitted — use get_page_info to see full template]';
            }
            if (isset($data['field_data']) && is_array($data['field_data'])) {
                // Summarize field data instead of including full values
                $summary = [];
                foreach ($data['field_data'] as $key => $val) {
                    if (is_array($val)) {
                        $summary[$key] = '[' . count($val) . ' items]';
                    } elseif (is_string($val) && strlen($val) > 100) {
                        $summary[$key] = mb_substr($val, 0, 100) . '...';
                    } else {
                        $summary[$key] = $val;
                    }
                }
                $data['field_data'] = $summary;
            }

            $truncated = $result;
            $truncated['data'] = $data;
            $encoded = json_encode($truncated);

            if (strlen($encoded) <= $maxSize) {
                return $encoded;
            }
        }

        // For scan_website: trim text_content
        if ($toolName === 'scan_website' && is_array($data)) {
            if (isset($data['text_content']) && strlen($data['text_content']) > 2000) {
                $data['text_content'] = mb_substr($data['text_content'], 0, 2000) . '\n[truncated]';
            }
            if (isset($data['subpages'])) {
                unset($data['subpages']);
                $data['_note'] = 'Subpages omitted to save tokens.';
            }

            $truncated = $result;
            $truncated['data'] = $data;
            $encoded = json_encode($truncated);

            if (strlen($encoded) <= $maxSize) {
                return $encoded;
            }
        }

        // For generate_image: always preserve full result (it's small and has usage_hint)
        if ($toolName === 'generate_image') {
            return json_encode($result);
        }

        // Generic fallback: preserve message and key metadata
        return json_encode([
            '_truncated' => true,
            'success' => $result['success'] ?? true,
            'message' => $result['message'] ?? ($result['success'] ? 'Tool executed successfully.' : 'Tool failed.'),
            '_summary' => 'Result was ' . strlen(json_encode($result)) . ' chars. Keys: ' . implode(', ', array_keys($data)),
        ]);
    }

    /**
     * Set the maximum number of tool call loops.
     */
    public function setMaxToolLoops(int $max): self
    {
        $this->maxToolLoops = $max;
        return $this;
    }
}
