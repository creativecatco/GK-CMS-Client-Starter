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
    protected int $maxToolLoops = 30;

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
     */
    public static function create(?int $userId = null): self
    {
        $provider = LlmProviderFactory::create();
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
            // 1. Add user message to conversation
            $conversation->addMessage('user', $userMessage);
            $conversation->generateTitle();

            // 2. Build the messages array
            $systemPrompt = $this->promptLoader->load();
            $messages = $this->buildMessages($systemPrompt, $conversation);

            // 3. Get tool definitions
            $tools = $this->toolRegistry->getToolDefinitions();

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

        // If the LLM returned tool calls, execute them and loop
        if (!empty($toolCallsReceived)) {
            // Save the assistant message with tool calls
            $conversation->addMessage('assistant', $fullContent, $toolCallsReceived);

            // Add the assistant message to the messages array for the next call
            $assistantMessage = [
                'role' => 'assistant',
                'content' => $fullContent ?: null,
                'tool_calls' => $toolCallsReceived,
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
                $messages[] = [
                    'role' => 'tool',
                    'tool_call_id' => $toolCallId,
                    'name' => $toolName,
                    'content' => $toolResultContent,
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
     * Set the maximum number of tool call loops.
     */
    public function setMaxToolLoops(int $max): self
    {
        $this->maxToolLoops = $max;
        return $this;
    }
}
