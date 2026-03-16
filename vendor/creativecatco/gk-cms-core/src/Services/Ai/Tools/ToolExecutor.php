<?php

namespace CreativeCatCo\GkCmsCore\Services\Ai\Tools;

use CreativeCatCo\GkCmsCore\Models\AiAction;
use CreativeCatCo\GkCmsCore\Models\AiConversation;
use Illuminate\Support\Facades\Log;

class ToolExecutor
{
    protected ToolRegistry $registry;

    public function __construct(ToolRegistry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * Execute a tool call from the LLM.
     *
     * @param string $toolName The tool to execute
     * @param array $params The parameters for the tool
     * @param AiConversation|null $conversation The conversation context (for logging)
     * @param int|null $userId The user who triggered this action
     * @return array The tool result
     */
    public function execute(string $toolName, array $params, ?AiConversation $conversation = null, ?int $userId = null): array
    {
        $tool = $this->registry->get($toolName);

        if (!$tool) {
            $result = [
                'success' => false,
                'message' => "Unknown tool: {$toolName}. Available tools: " . implode(', ', $this->registry->getToolNames()),
                'data' => null,
            ];

            $this->logAction($toolName, $params, $result, [], 'failed', $conversation, $userId);
            return $result;
        }

        // Capture rollback data before execution
        $rollbackData = [];
        try {
            $rollbackData = $tool->captureRollbackData($params);
        } catch (\Exception $e) {
            Log::warning("Failed to capture rollback data for {$toolName}", ['error' => $e->getMessage()]);
        }

        // Execute the tool
        try {
            $result = $tool->execute($params);

            $status = ($result['success'] ?? false) ? 'success' : 'failed';
            $this->logAction($toolName, $params, $result, $rollbackData, $status, $conversation, $userId);

            return $result;
        } catch (\Exception $e) {
            Log::error("Tool execution failed: {$toolName}", [
                'params' => $params,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $result = [
                'success' => false,
                'message' => "Tool execution error: {$e->getMessage()}",
                'data' => null,
            ];

            $this->logAction($toolName, $params, $result, $rollbackData, 'failed', $conversation, $userId);
            return $result;
        }
    }

    /**
     * Execute a tool call object from the LLM response.
     * Parses the function name and JSON arguments.
     */
    public function executeToolCall(array $toolCall, ?AiConversation $conversation = null, ?int $userId = null): array
    {
        $functionName = $toolCall['function']['name'] ?? '';
        $argumentsJson = $toolCall['function']['arguments'] ?? '{}';

        $params = json_decode($argumentsJson, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return [
                'success' => false,
                'message' => "Invalid JSON in tool arguments: " . json_last_error_msg(),
                'data' => null,
            ];
        }

        return $this->execute($functionName, $params ?? [], $conversation, $userId);
    }

    /**
     * Roll back a specific action.
     */
    public function rollbackAction(AiAction $action): bool
    {
        if (!$action->canRollback()) {
            return false;
        }

        $tool = $this->registry->get($action->tool_name);
        if (!$tool) {
            return false;
        }

        try {
            $success = $tool->rollback($action->rollback_data);
            if ($success) {
                $action->markRolledBack();
            }
            return $success;
        } catch (\Exception $e) {
            Log::error("Rollback failed for action {$action->id}", ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Log an action to the database.
     */
    protected function logAction(
        string $toolName,
        array $params,
        array $result,
        array $rollbackData,
        string $status,
        ?AiConversation $conversation,
        ?int $userId
    ): void {
        if (!$conversation) {
            return;
        }

        try {
            AiAction::create([
                'conversation_id' => $conversation->id,
                'user_id' => $userId ?? $conversation->user_id,
                'tool_name' => $toolName,
                'tool_input' => $params,
                'tool_output' => $result,
                'rollback_data' => $rollbackData,
                'status' => $status,
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to log AI action", ['error' => $e->getMessage()]);
        }
    }

    /**
     * Get the tool registry.
     */
    public function getRegistry(): ToolRegistry
    {
        return $this->registry;
    }
}
