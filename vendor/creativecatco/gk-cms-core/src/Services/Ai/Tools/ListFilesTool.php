<?php

namespace CreativeCatCo\GkCmsCore\Services\Ai\Tools;

use Illuminate\Support\Facades\Log;

/**
 * Tool for listing files and directories in the project.
 *
 * This gives the AI the ability to explore the project structure,
 * find relevant files, and understand what exists before making changes.
 */
class ListFilesTool extends AbstractTool
{
    public function name(): string
    {
        return 'list_files';
    }

    public function description(): string
    {
        return 'List files and directories in a project path. Use this to explore the project structure, find configuration files, discover custom plugins, or understand what files exist. Returns file names, sizes, and modification dates.';
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'path' => [
                    'type' => 'string',
                    'description' => 'Relative path from the project root to list (e.g., "app/", "config/", "database/migrations/", "public/images/"). Default: project root.',
                ],
                'recursive' => [
                    'type' => 'boolean',
                    'description' => 'Whether to list files recursively in subdirectories. Default: false. Use with caution on large directories.',
                ],
                'pattern' => [
                    'type' => 'string',
                    'description' => 'Optional glob pattern to filter files (e.g., "*.php", "*.blade.php", "*.js").',
                ],
            ],
            'required' => [],
        ];
    }

    public function execute(array $params): array
    {
        $relativePath = rtrim($params['path'] ?? '', '/');
        $recursive = $params['recursive'] ?? false;
        $pattern = $params['pattern'] ?? null;

        $basePath = base_path();
        $fullPath = $basePath . ($relativePath ? '/' . $relativePath : '');

        // Security: ensure path is within project root
        $realBase = realpath($basePath);
        $realPath = realpath($fullPath);

        if ($realPath === false) {
            return $this->error("Directory not found: {$relativePath}");
        }

        if (!str_starts_with($realPath, $realBase)) {
            return $this->error('Cannot list files outside the project directory.');
        }

        if (!is_dir($realPath)) {
            return $this->error("'{$relativePath}' is not a directory.");
        }

        try {
            $items = [];
            $maxItems = 200;

            if ($recursive) {
                $iterator = new \RecursiveIteratorIterator(
                    new \RecursiveDirectoryIterator($realPath, \RecursiveDirectoryIterator::SKIP_DOTS),
                    \RecursiveIteratorIterator::SELF_FIRST
                );

                foreach ($iterator as $file) {
                    if (count($items) >= $maxItems) break;

                    // Skip vendor, node_modules, .git
                    $relPath = str_replace($realBase . '/', '', $file->getPathname());
                    if (preg_match('#^(vendor|node_modules|\.git)/#', $relPath)) continue;

                    if ($pattern && !fnmatch($pattern, $file->getFilename())) continue;

                    $items[] = [
                        'path' => $relPath,
                        'type' => $file->isDir() ? 'directory' : 'file',
                        'size' => $file->isFile() ? $this->formatFileSize($file->getSize()) : null,
                        'modified' => date('Y-m-d H:i:s', $file->getMTime()),
                    ];
                }
            } else {
                $entries = scandir($realPath);
                foreach ($entries as $entry) {
                    if ($entry === '.' || $entry === '..') continue;
                    if (count($items) >= $maxItems) break;

                    if ($pattern && !fnmatch($pattern, $entry)) continue;

                    $entryPath = $realPath . '/' . $entry;
                    $relPath = ($relativePath ? $relativePath . '/' : '') . $entry;

                    $items[] = [
                        'path' => $relPath,
                        'type' => is_dir($entryPath) ? 'directory' : 'file',
                        'size' => is_file($entryPath) ? $this->formatFileSize(filesize($entryPath)) : null,
                        'modified' => date('Y-m-d H:i:s', filemtime($entryPath)),
                    ];
                }
            }

            // Sort: directories first, then files, alphabetically
            usort($items, function ($a, $b) {
                if ($a['type'] !== $b['type']) {
                    return $a['type'] === 'directory' ? -1 : 1;
                }
                return strcmp($a['path'], $b['path']);
            });

            return $this->success([
                'path' => $relativePath ?: '/',
                'items' => $items,
                'total' => count($items),
                'truncated' => count($items) >= $maxItems,
            ], "Listed " . count($items) . " items in {$relativePath}.");
        } catch (\Exception $e) {
            return $this->error('Failed to list files: ' . $e->getMessage());
        }
    }

    protected function formatFileSize(int $bytes): string
    {
        if ($bytes < 1024) return $bytes . ' B';
        if ($bytes < 1024 * 1024) return round($bytes / 1024, 1) . ' KB';
        return round($bytes / (1024 * 1024), 1) . ' MB';
    }
}
