<?php

namespace CreativeCatCo\GkCmsCore\Services\Ai\Tools;

abstract class AbstractTool implements ToolInterface
{
    /**
     * Get the OpenAI-compatible tool definition.
     */
    public function toToolDefinition(): array
    {
        return [
            'type' => 'function',
            'function' => [
                'name' => $this->name(),
                'description' => $this->description(),
                'parameters' => $this->parameters(),
            ],
        ];
    }

    /**
     * Default rollback implementation (no-op).
     * Override in subclasses that support rollback.
     */
    public function rollback(array $rollbackData): bool
    {
        return false;
    }

    /**
     * Default rollback data capture (empty).
     * Override in subclasses that support rollback.
     */
    public function captureRollbackData(array $params): array
    {
        return [];
    }

    /**
     * Helper: Return a success response.
     */
    protected function success(mixed $data = null, string $message = 'Success'): array
    {
        return [
            'success' => true,
            'data' => $data,
            'message' => $message,
        ];
    }

    /**
     * Helper: Return an error response.
     */
    protected function error(string $message, mixed $data = null): array
    {
        return [
            'success' => false,
            'data' => $data,
            'message' => $message,
        ];
    }
}
