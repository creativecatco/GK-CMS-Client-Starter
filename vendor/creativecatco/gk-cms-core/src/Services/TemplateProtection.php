<?php

namespace CreativeCatCo\GkCmsCore\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

/**
 * TemplateProtection
 *
 * Tracks which Blade template files have been modified by the customer
 * so that CMS updates never overwrite their customizations.
 *
 * How it works:
 * 1. On first install (or when a new template is added), we store the MD5 hash
 *    of each template file in a manifest (storage/cms/template-hashes.json).
 * 2. During an update, before publishing new templates, we compare the current
 *    file hash against the stored "original" hash.
 * 3. If the hashes match → the customer hasn't edited it → safe to overwrite.
 * 4. If the hashes differ → the customer has customized it → skip and log.
 * 5. New template files that don't exist yet are always copied.
 *
 * Admin views (filament/*, errors/*) are NEVER published as overrides.
 * They always load directly from the package to ensure updates take effect.
 * Any previously published admin views are automatically cleaned up.
 */
class TemplateProtection
{
    /**
     * Path to the manifest file that stores original template hashes.
     */
    protected string $manifestPath;

    /**
     * The source directory (package views).
     */
    protected string $sourceDir;

    /**
     * The destination directory (published views in the host app).
     */
    protected string $destDir;

    /**
     * Directories that should NEVER be published as overrides.
     * These always load from the package to ensure updates take effect immediately.
     */
    protected array $adminOnlyDirs = [
        'filament',
        'errors',
    ];

    public function __construct()
    {
        $this->manifestPath = storage_path('cms/template-hashes.json');
        $this->sourceDir = $this->getPackageViewsPath();
        $this->destDir = resource_path('views/vendor/cms-core');
    }

    /**
     * Get the package's views directory path.
     */
    protected function getPackageViewsPath(): string
    {
        return dirname(__DIR__) . '/../resources/views';
    }

    /**
     * Load the manifest of original template hashes.
     */
    public function loadManifest(): array
    {
        if (!file_exists($this->manifestPath)) {
            return [];
        }

        $data = json_decode(file_get_contents($this->manifestPath), true);
        return is_array($data) ? $data : [];
    }

    /**
     * Save the manifest of original template hashes.
     */
    public function saveManifest(array $manifest): void
    {
        $dir = dirname($this->manifestPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents(
            $this->manifestPath,
            json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );
    }

    /**
     * Build a fresh manifest from the current package source templates.
     * Called on first install or when resetting.
     * Only includes publishable templates (excludes admin views).
     */
    public function buildManifest(): array
    {
        $manifest = [];
        $files = $this->getPublishableTemplateFiles($this->sourceDir);

        foreach ($files as $relativePath) {
            $fullPath = $this->sourceDir . '/' . $relativePath;
            if (file_exists($fullPath)) {
                $manifest[$relativePath] = md5_file($fullPath);
            }
        }

        $this->saveManifest($manifest);
        return $manifest;
    }

    /**
     * Perform a safe template publish during an update.
     *
     * This method:
     * 1. Cleans up any stale published admin views (they should never be overrides)
     * 2. Publishes only frontend/customizable templates with customer protection
     *
     * Returns an array with:
     * - 'updated': files that were safely overwritten
     * - 'skipped': files that were skipped because the customer edited them
     * - 'new': files that were newly added
     * - 'cleaned': admin view files that were removed from the published directory
     */
    public function safePublish(): array
    {
        $manifest = $this->loadManifest();
        $result = ['updated' => [], 'skipped' => [], 'new' => [], 'cleaned' => []];

        // Step 1: Clean up stale published admin views
        $result['cleaned'] = $this->cleanupAdminViews();

        // Step 2: Only publish frontend/customizable templates
        $sourceFiles = $this->getPublishableTemplateFiles($this->sourceDir);

        // Ensure destination directory exists
        if (!is_dir($this->destDir)) {
            mkdir($this->destDir, 0755, true);
        }

        $newManifest = [];

        foreach ($sourceFiles as $relativePath) {
            $sourcePath = $this->sourceDir . '/' . $relativePath;
            $destPath = $this->destDir . '/' . $relativePath;
            $sourceHash = md5_file($sourcePath);

            // Store the new source hash in the manifest
            $newManifest[$relativePath] = $sourceHash;

            // Ensure subdirectory exists
            $destSubDir = dirname($destPath);
            if (!is_dir($destSubDir)) {
                mkdir($destSubDir, 0755, true);
            }

            // Case 1: Destination file doesn't exist → new file, always copy
            if (!file_exists($destPath)) {
                copy($sourcePath, $destPath);
                $result['new'][] = $relativePath;
                continue;
            }

            // Case 2: Destination file exists
            $destHash = md5_file($destPath);
            $originalHash = $manifest[$relativePath] ?? null;

            if ($originalHash === null) {
                // No record of the original — this file existed before we started tracking.
                // Be conservative: if the dest differs from source, assume customer edited it.
                if ($destHash !== $sourceHash) {
                    $result['skipped'][] = $relativePath;
                    // Keep the old manifest entry (dest hash) so we track from here
                    $newManifest[$relativePath] = $destHash;
                } else {
                    // Files are identical, safe to overwrite (no-op)
                    $result['updated'][] = $relativePath;
                }
                continue;
            }

            // Case 3: We have the original hash
            if ($destHash === $originalHash) {
                // Customer hasn't modified it → safe to overwrite with new version
                copy($sourcePath, $destPath);
                $result['updated'][] = $relativePath;
            } else {
                // Customer has modified it → DO NOT overwrite
                $result['skipped'][] = $relativePath;
                // Keep the original hash so future updates still detect the modification
                $newManifest[$relativePath] = $originalHash;
            }
        }

        // Remove manifest entries for admin views (they should not be tracked)
        foreach ($newManifest as $path => $hash) {
            if ($this->isAdminView($path)) {
                unset($newManifest[$path]);
            }
        }

        $this->saveManifest($newManifest);

        // Log the results
        if (!empty($result['cleaned'])) {
            Log::info('CMS Update: Cleaned up stale published admin views', [
                'cleaned' => $result['cleaned'],
            ]);
        }

        if (!empty($result['skipped'])) {
            Log::info('CMS Update: Skipped customer-modified templates', [
                'skipped' => $result['skipped'],
            ]);
        }

        return $result;
    }

    /**
     * Clean up any published admin views that should not exist as overrides.
     * These views should always load from the package directly.
     *
     * @return array List of cleaned up file paths
     */
    public function cleanupAdminViews(): array
    {
        $cleaned = [];

        foreach ($this->adminOnlyDirs as $dir) {
            $publishedDir = $this->destDir . '/' . $dir;

            if (is_dir($publishedDir)) {
                // Get all files before deleting
                $iterator = new \RecursiveIteratorIterator(
                    new \RecursiveDirectoryIterator($publishedDir, \RecursiveDirectoryIterator::SKIP_DOTS),
                    \RecursiveIteratorIterator::LEAVES_ONLY
                );

                foreach ($iterator as $file) {
                    $relativePath = ltrim(str_replace($this->destDir, '', $file->getPathname()), '/');
                    $cleaned[] = $relativePath;
                }

                // Remove the entire directory
                $this->deleteDirectory($publishedDir);
            }
        }

        // Also check for any individual admin-related files at the root level
        // that shouldn't be published (e.g., old 'admin/' directory)
        $adminDir = $this->destDir . '/admin';
        if (is_dir($adminDir)) {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($adminDir, \RecursiveDirectoryIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::LEAVES_ONLY
            );

            foreach ($iterator as $file) {
                $relativePath = ltrim(str_replace($this->destDir, '', $file->getPathname()), '/');
                $cleaned[] = $relativePath;
            }

            $this->deleteDirectory($adminDir);
        }

        // Also check for the 'settings' directory (admin settings views)
        $settingsDir = $this->destDir . '/settings';
        if (is_dir($settingsDir)) {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($settingsDir, \RecursiveDirectoryIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::LEAVES_ONLY
            );

            foreach ($iterator as $file) {
                $relativePath = ltrim(str_replace($this->destDir, '', $file->getPathname()), '/');
                $cleaned[] = $relativePath;
            }

            $this->deleteDirectory($settingsDir);
        }

        return $cleaned;
    }

    /**
     * Check if a relative path is an admin-only view that should not be published.
     */
    protected function isAdminView(string $relativePath): bool
    {
        foreach ($this->adminOnlyDirs as $dir) {
            if (str_starts_with($relativePath, $dir . '/')) {
                return true;
            }
        }

        // Also catch 'admin/' and 'settings/' prefixes
        if (str_starts_with($relativePath, 'admin/') || str_starts_with($relativePath, 'settings/')) {
            return true;
        }

        return false;
    }

    /**
     * Check if a specific template has been modified by the customer.
     */
    public function isModified(string $relativePath): bool
    {
        $manifest = $this->loadManifest();
        $destPath = $this->destDir . '/' . $relativePath;

        if (!file_exists($destPath)) {
            return false;
        }

        $originalHash = $manifest[$relativePath] ?? null;
        if ($originalHash === null) {
            return false; // No tracking data, assume unmodified
        }

        return md5_file($destPath) !== $originalHash;
    }

    /**
     * Get a list of all customer-modified templates.
     */
    public function getModifiedTemplates(): array
    {
        $manifest = $this->loadManifest();
        $modified = [];

        foreach ($manifest as $relativePath => $originalHash) {
            $destPath = $this->destDir . '/' . $relativePath;
            if (file_exists($destPath) && md5_file($destPath) !== $originalHash) {
                $modified[] = $relativePath;
            }
        }

        return $modified;
    }

    /**
     * Force-update a specific template (customer explicitly wants the new version).
     */
    public function forceUpdate(string $relativePath): bool
    {
        $sourcePath = $this->sourceDir . '/' . $relativePath;
        $destPath = $this->destDir . '/' . $relativePath;

        if (!file_exists($sourcePath)) {
            return false;
        }

        $destSubDir = dirname($destPath);
        if (!is_dir($destSubDir)) {
            mkdir($destSubDir, 0755, true);
        }

        copy($sourcePath, $destPath);

        // Update manifest with the new source hash
        $manifest = $this->loadManifest();
        $manifest[$relativePath] = md5_file($sourcePath);
        $this->saveManifest($manifest);

        return true;
    }

    /**
     * Get all template files relative to a base directory.
     * Returns ALL template files including admin views.
     */
    protected function getTemplateFiles(string $baseDir): array
    {
        $files = [];

        if (!is_dir($baseDir)) {
            return $files;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($baseDir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && str_ends_with($file->getFilename(), '.blade.php')) {
                $relativePath = ltrim(str_replace($baseDir, '', $file->getPathname()), '/');
                $files[] = $relativePath;
            }
        }

        sort($files);
        return $files;
    }

    /**
     * Get only publishable template files (excludes admin-only views).
     * These are the templates customers might want to customize.
     */
    protected function getPublishableTemplateFiles(string $baseDir): array
    {
        $allFiles = $this->getTemplateFiles($baseDir);

        return array_values(array_filter($allFiles, function ($path) {
            return !$this->isAdminView($path);
        }));
    }

    /**
     * Recursively delete a directory and all its contents.
     */
    protected function deleteDirectory(string $dir): bool
    {
        if (!is_dir($dir)) {
            return false;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $item) {
            if ($item->isDir()) {
                @rmdir($item->getPathname());
            } else {
                @unlink($item->getPathname());
            }
        }

        return @rmdir($dir);
    }
}
