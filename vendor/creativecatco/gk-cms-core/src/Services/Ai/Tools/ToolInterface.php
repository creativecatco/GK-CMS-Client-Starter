<?php

namespace CreativeCatCo\GkCmsCore\Services\Ai\Tools;

interface ToolInterface
{
    /**
     * Get the tool name (used in function calling).
     */
    public function name(): string;

    /**
     * Get a human-readable description of what this tool does.
     */
    public function description(): string;

    /**
     * Get the JSON Schema definition for the tool's parameters.
     * Follows the OpenAI function calling format.
     */
    public function parameters(): array;

    /**
     * Execute the tool with the given parameters.
     *
     * @param array $params The validated parameters
     * @return array ['success' => bool, 'data' => mixed, 'message' => string]
     */
    public function execute(array $params): array;

    /**
     * Roll back a previously executed action using stored rollback data.
     *
     * @param array $rollbackData The state captured before execution
     * @return bool Whether the rollback was successful
     */
    public function rollback(array $rollbackData): bool;

    /**
     * Capture the current state before execution (for undo support).
     *
     * @param array $params The parameters that will be used for execution
     * @return array The rollback data to store
     */
    public function captureRollbackData(array $params): array;

    /**
     * Get the OpenAI-compatible tool definition.
     */
    public function toToolDefinition(): array;
}
