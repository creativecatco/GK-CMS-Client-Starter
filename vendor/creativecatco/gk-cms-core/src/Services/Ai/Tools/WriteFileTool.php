<?php

namespace CreativeCatCo\GkCmsCore\Services\Ai\Tools;

use Illuminate\Support\Facades\Log;

/**
 * Tool for writing files to the project directory.
 *
 * Primarily intended for:
 * - Creating custom plugin files
 * - Writing custom JavaScript or CSS
 * - Creating configuration files
 * - Writing migration files for custom tables
 *
 * Safety features:
 * - Cannot write outside the project root
 * - Cannot overwrite .env files
 * - CMS core vendor files trigger a warning
 * - All writes are logged
 * - Creates directories as needed
 */
class WriteFileTool extends AbstractTool
{
    /**
     * Paths within the project that are considered CMS core.
     * Writing to these will trigger a warning.
     */
    protected array $cmsCorePaths = [
        'vendor/creativecatco/',
    ];

    /**
     * Paths that are always blocked.
     */
    protected array $blockedPaths = [
        '.env',
        'artisan',
    ];

    public function name(): string
    {
        return 'write_file';
    }

    public function description(): string
    {
        return 'Write or create a file in the project directory. Use this for creating custom plugins, scripts, configuration files, or database migration files. IMPORTANT: Do NOT write to CMS core files (vendor/creativecatco/) — this will break updates. Instead, create separate plugin files or use the app/ directory for customizations.';
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'path' => [
                    'type' => 'string',
                    'description' => 'Relative path from the project root (e.g., "app/Plugins/MyFeature.php", "public/js/custom.js", "database/migrations/2024_01_01_create_custom_table.php").',
                ],
                'content' => [
                    'type' => 'string',
                    'description' => 'The full content to write to the file.',
                ],
                'reason' => [
                    'type' => 'string',
                    'description' => 'Brief explanation of why this file is being created/modified.',
                ],
            ],
            'required' => ['path', 'content', 'reason'],
        ];
    }

    public function execute(array $params): array
    {
        $relativePath = $params['path'] ?? '';
        $content = $params['content'] ?? '';
        $reason = $params['reason'] ?? '';

        if (empty($relativePath)) {
            return $this->error('File path cannot be empty.');
        }

        // Security: block dangerous paths
        foreach ($this->blockedPaths as $blocked) {
            if (str_contains($relativePath, $blocked)) {
                return $this->error("Cannot write to '{$blocked}' — this file is protected.");
            }
        }

        // Check for CMS core paths
        foreach ($this->cmsCorePaths as $corePath) {
            if (str_starts_with($relativePath, $corePath)) {
                return $this->error(
                    "WARNING: '{$relativePath}' is a CMS core file. Modifying CMS core files will:\n" .
                    "1. Break when the CMS is updated\n" .
                    "2. Potentially crash the entire site\n" .
                    "3. Make it impossible to receive future updates\n\n" .
                    "Instead, create a custom plugin in the app/ directory or use the public/ directory for custom assets. " .
                    "If the user explicitly insists on modifying CMS core, they should do it manually."
                );
            }
        }

        // Resolve the full path
        $basePath = base_path();
        $fullPath = $basePath . '/' . $relativePath;

        // Security: ensure the file is within the project root
        $realBase = realpath($basePath);
        $parentDir = dirname($fullPath);
        if (!str_starts_with($fullPath, $realBase . '/')) {
            return $this->error('Cannot write files outside the project directory.');
        }

        try {
            // Create directory if it doesn't exist
            if (!is_dir($parentDir)) {
                mkdir($parentDir, 0755, true);
            }

            // Check if file already exists (for logging)
            $existed = file_exists($fullPath);
            $previousContent = $existed ? file_get_contents($fullPath) : null;

            // Write the file
            file_put_contents($fullPath, $content);

            Log::info('AI WriteFile', [
                'path' => $relativePath,
                'reason' => $reason,
                'existed' => $existed,
                'size' => strlen($content),
            ]);

            return $this->success([
                'path' => $relativePath,
                'action' => $existed ? 'updated' : 'created',
                'size' => $this->formatFileSize(strlen($content)),
            ], ($existed ? 'Updated' : 'Created') . " file: {$relativePath}");
        } catch (\Exception $e) {
            Log::error('AI WriteFile error', [
                'path' => $relativePath,
                'error' => $e->getMessage(),
            ]);
            return $this->error('Failed to write file: ' . $e->getMessage());
        }
    }

    public function captureRollbackData(array $params): array
    {
        $relativePath = $params['path'] ?? '';
        $fullPath = base_path() . '/' . $relativePath;

        return [
            'path' => $relativePath,
            'existed' => file_exists($fullPath),
            'previous_content' => file_exists($fullPath) ? file_get_contents($fullPath) : null,
        ];
    }

    public function rollback(array $rollbackData): bool
    {
        $fullPath = base_path() . '/' . ($rollbackData['path'] ?? '');

        try {
            if ($rollbackData['existed'] && $rollbackData['previous_content'] !== null) {
                file_put_contents($fullPath, $rollbackData['previous_content']);
            } elseif (!$rollbackData['existed'] && file_exists($fullPath)) {
                unlink($fullPath);
            }
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    protected function formatFileSize(int $bytes): string
    {
        if ($bytes < 1024) return $bytes . ' B';
        if ($bytes < 1024 * 1024) return round($bytes / 1024, 1) . ' KB';
        return round($bytes / (1024 * 1024), 1) . ' MB';
    }
}
