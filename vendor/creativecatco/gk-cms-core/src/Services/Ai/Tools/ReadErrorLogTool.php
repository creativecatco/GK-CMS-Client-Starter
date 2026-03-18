<?php

namespace CreativeCatCo\GkCmsCore\Services\Ai\Tools;

use Illuminate\Support\Facades\Log;

/**
 * Tool for reading the Laravel error log.
 *
 * This is a convenience wrapper around ReadFileTool specifically for
 * error logs. It reads the most recent entries and parses them into
 * a structured format for easier analysis.
 *
 * The AI should use this when:
 * - A page is broken and it needs to understand why
 * - A tool call failed and it needs to debug
 * - The user reports an error
 */
class ReadErrorLogTool extends AbstractTool
{
    public function name(): string
    {
        return 'read_error_log';
    }

    public function description(): string
    {
        return 'Read the most recent Laravel error log entries. ALWAYS use this FIRST when something goes wrong — broken pages, failed operations, PHP errors, template issues, etc. Returns structured log entries with timestamps, levels, messages, and stack traces. This is your primary debugging tool.';
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'lines' => [
                    'type' => 'integer',
                    'description' => 'Number of lines to read from the end of the log file. Default: 100. Max: 500.',
                ],
                'filter' => [
                    'type' => 'string',
                    'description' => 'Optional filter keyword to show only matching log entries (e.g., "ERROR", "Blade", "Undefined", a specific page slug, "template").',
                ],
            ],
            'required' => [],
        ];
    }

    public function execute(array $params): array
    {
        $maxLines = min($params['lines'] ?? 100, 500);
        $filter = $params['filter'] ?? null;

        $logPath = storage_path('logs/laravel.log');

        if (!file_exists($logPath)) {
            return $this->success([
                'entries' => [],
                'message' => 'No log file found — this usually means no errors have occurred.',
            ], 'No log file found.');
        }

        try {
            // Read the last N lines
            $rawContent = $this->readLastLines($logPath, $maxLines);

            // Parse into individual log entries
            $entries = $this->parseLogEntries($rawContent);

            // Apply filter if specified
            if ($filter) {
                $entries = array_values(array_filter($entries, function ($entry) use ($filter) {
                    return stripos($entry['raw'], $filter) !== false;
                }));
            }

            // Limit to most recent 20 entries for readability
            $totalEntries = count($entries);
            $entries = array_slice($entries, -20);

            // Extract the most recent errors specifically
            $recentErrors = array_values(array_filter($entries, function ($entry) {
                return in_array($entry['level'], ['ERROR', 'CRITICAL', 'EMERGENCY', 'ALERT']);
            }));

            $errorCount = count($recentErrors);

            // Build a helpful summary
            $summary = $errorCount > 0
                ? "Found {$totalEntries} log entries, {$errorCount} are errors."
                : "Found {$totalEntries} log entries, no recent errors.";

            // Add the most recent error details to the summary for quick reference
            if ($errorCount > 0) {
                $latestError = end($recentErrors);
                $summary .= " Latest error: [{$latestError['timestamp']}] {$latestError['message']}";
            }

            return $this->success([
                'total_entries' => $totalEntries,
                'shown_entries' => count($entries),
                'recent_error_count' => $errorCount,
                'entries' => $entries,
                'log_file_size' => $this->formatFileSize(filesize($logPath)),
            ], $summary);
        } catch (\Exception $e) {
            return $this->error('Failed to read error log: ' . $e->getMessage());
        }
    }

    /**
     * Parse raw log content into structured entries.
     */
    protected function parseLogEntries(string $content): array
    {
        $entries = [];
        $lines = explode("\n", $content);
        $currentEntry = null;

        foreach ($lines as $line) {
            // Match Laravel log format: [2024-01-15 10:30:45] production.ERROR: Message
            if (preg_match('/^\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\] \w+\.(\w+): (.*)$/', $line, $matches)) {
                // Save previous entry
                if ($currentEntry !== null) {
                    $entries[] = $currentEntry;
                }

                $currentEntry = [
                    'timestamp' => $matches[1],
                    'level' => $matches[2],
                    'message' => $matches[3],
                    'raw' => $line,
                    'stack_trace' => '',
                ];
            } elseif ($currentEntry !== null) {
                // Append to current entry's stack trace (limit size)
                if (strlen($currentEntry['stack_trace']) < 2000) {
                    $currentEntry['stack_trace'] .= $line . "\n";
                }
                $currentEntry['raw'] .= "\n" . $line;
            }
        }

        // Don't forget the last entry
        if ($currentEntry !== null) {
            $entries[] = $currentEntry;
        }

        // Clean up stack traces
        foreach ($entries as &$entry) {
            $entry['stack_trace'] = trim($entry['stack_trace']);
            if (empty($entry['stack_trace'])) {
                unset($entry['stack_trace']);
            }
        }

        return $entries;
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

    protected function formatFileSize(int $bytes): string
    {
        if ($bytes < 1024) return $bytes . ' B';
        if ($bytes < 1024 * 1024) return round($bytes / 1024, 1) . ' KB';
        return round($bytes / (1024 * 1024), 1) . ' MB';
    }
}
