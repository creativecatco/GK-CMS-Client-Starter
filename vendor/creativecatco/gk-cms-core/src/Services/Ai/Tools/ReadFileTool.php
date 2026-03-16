<?php

namespace CreativeCatCo\GkCmsCore\Services\Ai\Tools;

use Illuminate\Support\Facades\Log;

/**
 * Tool for reading files from the project directory.
 *
 * This allows the AI to inspect configuration files, templates, logs,
 * and other project files to understand the current state of the system.
 *
 * Safety features:
 * - Cannot read files outside the project root
 * - Cannot read .env files (contains secrets)
 * - File size limits to prevent memory issues
 */
class ReadFileTool extends AbstractTool
{
    public function name(): string
    {
        return 'read_file';
    }

    public function description(): string
    {
        return 'Read the contents of a file from the project directory. Use this to inspect configuration, templates, error logs, custom code, or any file to understand the current state. Cannot read .env files or files outside the project root. Useful for debugging issues and understanding the system setup.';
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'path' => [
                    'type' => 'string',
                    'description' => 'Relative path from the project root (e.g., "config/app.php", "storage/logs/laravel.log", "resources/views/pages/home.blade.php"). Use "storage/logs/laravel.log" to read error logs.',
                ],
                'lines' => [
                    'type' => 'integer',
                    'description' => 'Maximum number of lines to read. For log files, reads the LAST N lines. For other files, reads the FIRST N lines. Default: 200.',
                ],
            ],
            'required' => ['path'],
        ];
    }

    public function execute(array $params): array
    {
        $relativePath = $params['path'] ?? '';
        $maxLines = min($params['lines'] ?? 200, 500);

        if (empty($relativePath)) {
            return $this->error('File path cannot be empty.');
        }

        // Security: block .env files
        if (preg_match('/\.env/i', basename($relativePath))) {
            return $this->error('Cannot read .env files — they contain sensitive credentials.');
        }

        // Resolve the full path
        $basePath = base_path();
        $fullPath = realpath($basePath . '/' . $relativePath);

        // If realpath fails (file doesn't exist), try the raw path
        if ($fullPath === false) {
            $fullPath = $basePath . '/' . $relativePath;
            if (!file_exists($fullPath)) {
                return $this->error("File not found: {$relativePath}");
            }
        }

        // Security: ensure the file is within the project root
        if (!str_starts_with($fullPath, $basePath)) {
            return $this->error('Cannot read files outside the project directory.');
        }

        // Check file size
        $fileSize = filesize($fullPath);
        if ($fileSize > 5 * 1024 * 1024) { // 5MB limit
            return $this->error("File is too large (" . round($fileSize / 1024 / 1024, 1) . " MB). Maximum is 5MB.");
        }

        try {
            $isLogFile = str_contains($relativePath, 'log') || str_ends_with($relativePath, '.log');

            if ($isLogFile) {
                // For log files, read the last N lines
                $content = $this->readLastLines($fullPath, $maxLines);
            } else {
                // For other files, read the first N lines
                $content = $this->readFirstLines($fullPath, $maxLines);
            }

            $totalLines = $this->countLines($fullPath);

            return $this->success([
                'path' => $relativePath,
                'content' => $content,
                'total_lines' => $totalLines,
                'lines_shown' => min($maxLines, $totalLines),
                'file_size' => $this->formatFileSize($fileSize),
                'reading_from' => $isLogFile ? 'end' : 'start',
            ], "Read {$relativePath} successfully.");
        } catch (\Exception $e) {
            Log::error('AI ReadFile error', [
                'path' => $relativePath,
                'error' => $e->getMessage(),
            ]);
            return $this->error('Failed to read file: ' . $e->getMessage());
        }
    }

    protected function readLastLines(string $path, int $lines): string
    {
        $file = new \SplFileObject($path, 'r');
        $file->seek(PHP_INT_MAX);
        $totalLines = $file->key();

        $startLine = max(0, $totalLines - $lines);
        $file->seek($startLine);

        $content = '';
        while (!$file->eof()) {
            $content .= $file->fgets();
        }

        return $content;
    }

    protected function readFirstLines(string $path, int $lines): string
    {
        $file = new \SplFileObject($path, 'r');
        $content = '';
        $lineCount = 0;

        while (!$file->eof() && $lineCount < $lines) {
            $content .= $file->fgets();
            $lineCount++;
        }

        return $content;
    }

    protected function countLines(string $path): int
    {
        $file = new \SplFileObject($path, 'r');
        $file->seek(PHP_INT_MAX);
        return $file->key() + 1;
    }

    protected function formatFileSize(int $bytes): string
    {
        if ($bytes < 1024) return $bytes . ' B';
        if ($bytes < 1024 * 1024) return round($bytes / 1024, 1) . ' KB';
        return round($bytes / (1024 * 1024), 1) . ' MB';
    }
}
