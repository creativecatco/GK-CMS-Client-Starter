<?php

namespace CreativeCatCo\GkCmsCore\Services;

use CreativeCatCo\GkCmsCore\Models\Setting;
use Illuminate\Support\Facades\Log;

/**
 * UpdateService
 *
 * Orchestrates the CMS update pipeline with two update channels:
 *
 * 1. 'composer' channel (development):
 *    - Uses `composer update` to pull latest code from the private GitHub repo
 *    - Requires a GitHub token and auth.json
 *
 * 2. 'release' channel (customer/production):
 *    - Downloads pre-built release zips from GitHub Releases
 *    - No GitHub token needed (public repo releases)
 *    - Extracts and merges files, protecting customer content
 *
 * Both channels share the same post-update pipeline:
 * - Database migrations (additive only — never drops columns or tables)
 * - Safe template publish (skips customer-modified templates)
 * - Asset publish (admin panel assets — always overwritten)
 * - Cache clear
 *
 * Content protection guarantees:
 * - Database content (pages, posts, products, menus, settings) is NEVER touched
 * - Customer-modified Blade templates are NEVER overwritten
 * - Media/uploaded files are NEVER touched
 * - Only CMS core PHP code, admin assets, and unmodified templates are updated
 */
class UpdateService
{
    protected string $basePath;
    protected string $logFile;
    protected string $lockFile;
    protected string $channel;

    public function __construct()
    {
        $this->basePath = base_path();
        $this->logFile = storage_path('logs/cms-update.log');
        $this->lockFile = storage_path('logs/cms-update.lock');
        $this->channel = config('cms.update_channel', 'release');
    }

    /**
     * Get the current update channel.
     */
    public function getChannel(): string
    {
        return $this->channel;
    }

    /**
     * Run pre-flight checks before starting an update.
     *
     * @return array ['pass' => bool, 'checks' => [...]]
     */
    public function preFlightChecks(): array
    {
        $checks = [];

        // 1. PHP version
        $phpVersion = PHP_VERSION;
        $checks['php_version'] = [
            'label' => 'PHP Version',
            'value' => $phpVersion,
            'pass' => version_compare($phpVersion, '8.2.0', '>='),
            'message' => version_compare($phpVersion, '8.2.0', '>=')
                ? "PHP {$phpVersion} meets the requirement (>= 8.2)"
                : "PHP {$phpVersion} is below the minimum required version 8.2",
        ];

        // 2. Disk space (need at least 100MB free for release channel, 50MB for composer)
        $freeSpace = @disk_free_space($this->basePath);
        $freeSpaceMb = $freeSpace ? round($freeSpace / 1024 / 1024) : 0;
        $requiredMb = $this->channel === 'release' ? 100 : 50;
        $checks['disk_space'] = [
            'label' => 'Disk Space',
            'value' => "{$freeSpaceMb} MB free",
            'pass' => $freeSpaceMb >= $requiredMb,
            'message' => $freeSpaceMb >= $requiredMb
                ? "Sufficient disk space available"
                : "Less than {$requiredMb} MB free — update may fail",
        ];

        // 3. Vendor directory writable
        $vendorWritable = is_writable($this->basePath . '/vendor');
        $checks['vendor_writable'] = [
            'label' => 'Vendor Directory',
            'value' => $vendorWritable ? 'Writable' : 'Not writable',
            'pass' => $vendorWritable,
            'message' => $vendorWritable
                ? "Vendor directory is writable"
                : "Vendor directory is not writable — update will fail",
        ];

        // 4. Channel-specific checks
        if ($this->channel === 'composer') {
            // Composer binary required
            $composerPath = $this->findComposer();
            $checks['composer'] = [
                'label' => 'Composer',
                'value' => $composerPath ?: 'Not found',
                'pass' => !empty($composerPath),
                'message' => !empty($composerPath)
                    ? "Composer found at {$composerPath}"
                    : "Composer binary not found on the system",
            ];

            // GitHub token required for private repo
            $hasToken = !empty(Setting::get('github_token'));
            $checks['github_token'] = [
                'label' => 'GitHub Token',
                'value' => $hasToken ? 'Configured' : 'Missing',
                'pass' => $hasToken,
                'message' => $hasToken
                    ? "GitHub token is configured for private repo access"
                    : "No GitHub token — update will fail for private repos",
            ];
        } else {
            // Release channel: need curl or allow_url_fopen
            $canDownload = function_exists('curl_init') || ini_get('allow_url_fopen');
            $checks['download'] = [
                'label' => 'File Download',
                'value' => $canDownload ? 'Available' : 'Not available',
                'pass' => $canDownload,
                'message' => $canDownload
                    ? "cURL or allow_url_fopen available for downloading releases"
                    : "Neither cURL nor allow_url_fopen is available — cannot download releases",
            ];

            // Zip extension required
            $hasZip = extension_loaded('zip');
            $checks['zip'] = [
                'label' => 'Zip Extension',
                'value' => $hasZip ? 'Enabled' : 'Missing',
                'pass' => $hasZip,
                'message' => $hasZip
                    ? "Zip extension is available for extracting releases"
                    : "Zip extension is missing — cannot extract release files",
            ];
        }

        // 5. No update currently running
        $isLocked = $this->isUpdateRunning();
        $checks['no_lock'] = [
            'label' => 'Update Lock',
            'value' => $isLocked ? 'Locked' : 'Clear',
            'pass' => !$isLocked,
            'message' => $isLocked
                ? "An update is already in progress"
                : "No update currently running",
        ];

        $allPass = collect($checks)->every(fn($c) => $c['pass']);

        return [
            'pass' => $allPass,
            'checks' => $checks,
        ];
    }

    /**
     * Check if an update is currently running.
     */
    public function isUpdateRunning(): bool
    {
        if (!file_exists($this->lockFile)) {
            return false;
        }

        $lockAge = time() - filemtime($this->lockFile);

        // Stale lock: auto-clean after 10 minutes
        if ($lockAge > 600) {
            @unlink($this->lockFile);
            Log::warning('CMS Update: Cleaned up stale lock file', ['age_seconds' => $lockAge]);
            return false;
        }

        return true;
    }

    /**
     * Generate and execute the background update script.
     *
     * @param string|null $downloadUrl  For release channel: the URL of the release zip
     * @return array ['success' => bool, 'message' => string]
     */
    public function startUpdate(?string $downloadUrl = null): array
    {
        if ($this->isUpdateRunning()) {
            return [
                'success' => false,
                'status' => 'running',
                'message' => 'An update is already in progress.',
            ];
        }

        // Build template hash manifest before update
        $this->ensureTemplateManifest();

        // Create lock file
        file_put_contents($this->lockFile, json_encode([
            'started_at' => date('Y-m-d H:i:s'),
            'pid' => getmypid(),
            'channel' => $this->channel,
        ]));

        // Clear the log file
        file_put_contents($this->logFile, "=== GKeys CMS Update ===\n");
        file_put_contents($this->logFile, "Channel: {$this->channel}\n", FILE_APPEND);
        file_put_contents($this->logFile, "Started: " . date('Y-m-d H:i:s') . "\n\n", FILE_APPEND);

        $phpBinary = PHP_BINARY ?: 'php';

        if ($this->channel === 'composer') {
            // Composer channel: ensure auth.json and run composer update
            $this->ensureAuthJson();
            $composerPath = $this->findComposer();
            $script = $this->buildComposerUpdateScript($composerPath, $phpBinary);
        } else {
            // Release channel: download zip and extract
            if (empty($downloadUrl)) {
                @unlink($this->lockFile);
                return [
                    'success' => false,
                    'status' => 'error',
                    'message' => 'No download URL provided for release update.',
                ];
            }
            $script = $this->buildReleaseUpdateScript($downloadUrl, $phpBinary);
        }

        $scriptFile = storage_path('logs/cms-update.sh');
        file_put_contents($scriptFile, $script);
        chmod($scriptFile, 0755);

        // Run in background
        exec("nohup bash {$scriptFile} > /dev/null 2>&1 &");

        return [
            'success' => true,
            'status' => 'started',
            'message' => 'Update started in background.',
        ];
    }

    /**
     * Build the bash update script for the COMPOSER channel.
     */
    protected function buildComposerUpdateScript(string $composerPath, string $phpBinary): string
    {
        $basePath = $this->basePath;
        $logFile = $this->logFile;
        $lockFile = $this->lockFile;

        return <<<BASH
#!/bin/bash
cd "{$basePath}"

# ─── Step 1: Composer Update (only the CMS core package) ───
echo "[1/5] Running composer update..." >> "{$logFile}"
{$composerPath} update creativecatco/gk-cms-core --no-interaction --prefer-dist --no-dev 2>> "{$logFile}"
COMPOSER_EXIT=\$?
if [ \$COMPOSER_EXIT -ne 0 ]; then
    echo "" >> "{$logFile}"
    echo "ERROR: Composer update failed with exit code \$COMPOSER_EXIT" >> "{$logFile}"
    echo "UPDATE_FAILED" >> "{$logFile}"
    rm -f "{$lockFile}"
    exit 1
fi
echo "  Composer update completed successfully." >> "{$logFile}"

# ─── Step 2: Database Migrations (additive only) ───
echo "" >> "{$logFile}"
echo "[2/5] Running database migrations..." >> "{$logFile}"
{$phpBinary} artisan migrate --force >> "{$logFile}" 2>&1

# ─── Step 3: Safe Template Publish (protects customer edits) ───
echo "" >> "{$logFile}"
echo "[3/5] Publishing templates (protecting customer edits)..." >> "{$logFile}"
{$phpBinary} artisan cms:safe-publish-templates >> "{$logFile}" 2>&1

# ─── Step 4: Publish Admin Assets (always overwrite) ───
echo "" >> "{$logFile}"
echo "[4/5] Publishing admin assets..." >> "{$logFile}"
{$phpBinary} artisan vendor:publish --tag=cms-assets --force >> "{$logFile}" 2>&1

# ─── Step 5: Clear Caches ───
echo "" >> "{$logFile}"
echo "[5/5] Clearing caches..." >> "{$logFile}"
{$phpBinary} artisan cache:clear >> "{$logFile}" 2>&1
{$phpBinary} artisan view:clear >> "{$logFile}" 2>&1
{$phpBinary} artisan config:clear >> "{$logFile}" 2>&1
{$phpBinary} artisan route:clear >> "{$logFile}" 2>&1
{$phpBinary} artisan filament:cache-components >> "{$logFile}" 2>&1 || true

# ─── Done ───
echo "" >> "{$logFile}"
echo "Finished: \$(date '+%Y-%m-%d %H:%M:%S')" >> "{$logFile}"
echo "UPDATE_COMPLETE" >> "{$logFile}"

rm -f "{$lockFile}"
BASH;
    }

    /**
     * Build the bash update script for the RELEASE channel.
     * Downloads a pre-built zip, extracts it, and merges files safely.
     */
    protected function buildReleaseUpdateScript(string $downloadUrl, string $phpBinary): string
    {
        $basePath = $this->basePath;
        $logFile = $this->logFile;
        $lockFile = $this->lockFile;
        $storagePath = storage_path();
        $escapedUrl = escapeshellarg($downloadUrl);

        return <<<BASH
#!/bin/bash
cd "{$basePath}"

TEMP_DIR="{$storagePath}/cms-update-temp-\$\$"
ZIP_FILE="{$storagePath}/cms-release-download.zip"

# ─── Step 1: Download the release zip ───
echo "[1/7] Downloading release..." >> "{$logFile}"
if command -v curl &> /dev/null; then
    curl -sL -o "\$ZIP_FILE" {$escapedUrl} 2>> "{$logFile}"
elif command -v wget &> /dev/null; then
    wget -q -O "\$ZIP_FILE" {$escapedUrl} 2>> "{$logFile}"
else
    # Fallback: use PHP to download
    {$phpBinary} -r "file_put_contents('{$storagePath}/cms-release-download.zip', file_get_contents({$escapedUrl}));" 2>> "{$logFile}"
fi

if [ ! -f "\$ZIP_FILE" ] || [ ! -s "\$ZIP_FILE" ]; then
    echo "ERROR: Download failed or file is empty." >> "{$logFile}"
    echo "UPDATE_FAILED" >> "{$logFile}"
    rm -f "{$lockFile}" "\$ZIP_FILE"
    exit 1
fi

FILESIZE=\$(stat -c%s "\$ZIP_FILE" 2>/dev/null || stat -f%z "\$ZIP_FILE" 2>/dev/null)
echo "  Downloaded \$((FILESIZE / 1024 / 1024)) MB" >> "{$logFile}"

# ─── Step 2: Extract the zip ───
echo "" >> "{$logFile}"
echo "[2/7] Extracting release..." >> "{$logFile}"
mkdir -p "\$TEMP_DIR"
{$phpBinary} -r "
\\\$zip = new ZipArchive();
if (\\\$zip->open('{$storagePath}/cms-release-download.zip') === true) {
    \\\$zip->extractTo('\$TEMP_DIR');
    \\\$zip->close();
    echo 'Extraction complete.';
} else {
    echo 'ERROR: Failed to extract zip.';
    exit(1);
}
" >> "{$logFile}" 2>&1

EXTRACT_EXIT=\$?
if [ \$EXTRACT_EXIT -ne 0 ]; then
    echo "UPDATE_FAILED" >> "{$logFile}"
    rm -f "{$lockFile}" "\$ZIP_FILE"
    rm -rf "\$TEMP_DIR"
    exit 1
fi

# Find the extracted directory (may be gkeys-cms/ or similar)
SOURCE_DIR=\$(find "\$TEMP_DIR" -maxdepth 1 -type d ! -name "\$(basename \$TEMP_DIR)" | head -1)
if [ -z "\$SOURCE_DIR" ]; then
    SOURCE_DIR="\$TEMP_DIR"
fi

echo "  Source directory: \$SOURCE_DIR" >> "{$logFile}"

# ─── Step 3: Update vendor directory (the core package) ───
echo "" >> "{$logFile}"
echo "[3/7] Updating vendor packages..." >> "{$logFile}"

# Only update the CMS core package in vendor
if [ -d "\$SOURCE_DIR/vendor/creativecatco" ]; then
    rm -rf "{$basePath}/vendor/creativecatco"
    cp -r "\$SOURCE_DIR/vendor/creativecatco" "{$basePath}/vendor/creativecatco"
    echo "  Updated creativecatco/gk-cms-core package." >> "{$logFile}"
fi

# Update composer autoload files
if [ -f "\$SOURCE_DIR/vendor/autoload.php" ]; then
    cp "\$SOURCE_DIR/vendor/autoload.php" "{$basePath}/vendor/autoload.php"
fi
if [ -d "\$SOURCE_DIR/vendor/composer" ]; then
    rm -rf "{$basePath}/vendor/composer"
    cp -r "\$SOURCE_DIR/vendor/composer" "{$basePath}/vendor/composer"
    echo "  Updated composer autoload files." >> "{$logFile}"
fi

# Update other vendor packages that may have changed
for pkg_dir in "\$SOURCE_DIR/vendor/"*/; do
    pkg_name=\$(basename "\$pkg_dir")
    # Skip already handled dirs
    if [ "\$pkg_name" = "creativecatco" ] || [ "\$pkg_name" = "composer" ] || [ "\$pkg_name" = "bin" ]; then
        continue
    fi
    if [ -d "\$pkg_dir" ]; then
        rm -rf "{$basePath}/vendor/\$pkg_name"
        cp -r "\$pkg_dir" "{$basePath}/vendor/\$pkg_name"
    fi
done
echo "  All vendor packages updated." >> "{$logFile}"

# Update composer.json and composer.lock if present
if [ -f "\$SOURCE_DIR/composer.json" ]; then
    cp "\$SOURCE_DIR/composer.json" "{$basePath}/composer.json"
fi
if [ -f "\$SOURCE_DIR/composer.lock" ]; then
    cp "\$SOURCE_DIR/composer.lock" "{$basePath}/composer.lock"
fi

# ─── Step 4: Database Migrations (additive only) ───
echo "" >> "{$logFile}"
echo "[4/7] Running database migrations..." >> "{$logFile}"
{$phpBinary} artisan migrate --force >> "{$logFile}" 2>&1

# ─── Step 5: Safe Template Publish (protects customer edits) ───
echo "" >> "{$logFile}"
echo "[5/7] Publishing templates (protecting customer edits)..." >> "{$logFile}"
{$phpBinary} artisan cms:safe-publish-templates >> "{$logFile}" 2>&1

# ─── Step 6: Publish Admin Assets (always overwrite) ───
echo "" >> "{$logFile}"
echo "[6/7] Publishing admin assets..." >> "{$logFile}"
{$phpBinary} artisan vendor:publish --tag=cms-assets --force >> "{$logFile}" 2>&1

# ─── Step 7: Clear Caches ───
echo "" >> "{$logFile}"
echo "[7/7] Clearing caches..." >> "{$logFile}"
{$phpBinary} artisan cache:clear >> "{$logFile}" 2>&1
{$phpBinary} artisan view:clear >> "{$logFile}" 2>&1
{$phpBinary} artisan config:clear >> "{$logFile}" 2>&1
{$phpBinary} artisan route:clear >> "{$logFile}" 2>&1
{$phpBinary} artisan filament:cache-components >> "{$logFile}" 2>&1 || true

# ─── Cleanup ───
echo "" >> "{$logFile}"
echo "Cleaning up temporary files..." >> "{$logFile}"
rm -f "\$ZIP_FILE"
rm -rf "\$TEMP_DIR"

# ─── Done ───
echo "" >> "{$logFile}"
echo "Finished: \$(date '+%Y-%m-%d %H:%M:%S')" >> "{$logFile}"
echo "UPDATE_COMPLETE" >> "{$logFile}"

rm -f "{$lockFile}"
BASH;
    }

    /**
     * Get the current update status by reading the log file.
     */
    public function getStatus(): array
    {
        if (!file_exists($this->logFile)) {
            return [
                'status' => 'idle',
                'log' => '',
            ];
        }

        $log = file_get_contents($this->logFile);
        $isComplete = str_contains($log, 'UPDATE_COMPLETE');
        $isFailed = str_contains($log, 'UPDATE_FAILED');
        $isRunning = file_exists($this->lockFile);

        // Clean up markers for display
        $displayLog = str_replace('UPDATE_COMPLETE', '--- Update completed successfully! ---', $log);
        $displayLog = str_replace('UPDATE_FAILED', '--- Update failed. See errors above. ---', $displayLog);

        if ($isComplete) {
            @unlink($this->lockFile);
            \Illuminate\Support\Facades\Cache::forget('cms_update_check');

            if (function_exists('opcache_reset')) {
                @opcache_reset();
            }
        }

        $status = 'idle';
        if ($isComplete) {
            $status = 'complete';
        } elseif ($isFailed) {
            $status = 'failed';
            @unlink($this->lockFile);
        } elseif ($isRunning) {
            $status = 'running';
        }

        return [
            'status' => $status,
            'log' => $displayLog,
        ];
    }

    /**
     * Get the currently installed version from the package's composer.json.
     */
    public function getInstalledVersion(): string
    {
        // First try the config (most reliable after cache clear)
        $configVersion = config('cms.version');
        if ($configVersion && $configVersion !== '0.0.0') {
            return $configVersion;
        }

        // Fallback: read from the package's own composer.json
        $packageComposer = dirname(__DIR__) . '/../composer.json';
        if (file_exists($packageComposer)) {
            $data = json_decode(file_get_contents($packageComposer), true);
            if (!empty($data['version'])) {
                return $data['version'];
            }
        }

        // Fallback: read from composer.lock
        $composerLock = base_path('composer.lock');
        if (file_exists($composerLock)) {
            $lock = json_decode(file_get_contents($composerLock), true);
            foreach ($lock['packages'] ?? [] as $package) {
                if ($package['name'] === 'creativecatco/gk-cms-core') {
                    return $package['version'] ?? 'dev-main';
                }
            }
        }

        return 'dev-main';
    }

    /**
     * Check for the latest available version.
     *
     * For 'composer' channel: checks gk-cms-core releases on GitHub.
     * For 'release' channel: checks GK-CMS-Client-Starter releases on GitHub.
     *
     * @return array|null ['version', 'tag', 'date', 'notes', 'url', 'download_url']
     */
    public function checkLatestVersion(): ?array
    {
        if ($this->channel === 'composer') {
            $repo = 'creativecatco/gk-cms-core';
            $token = $this->getGithubToken();
        } else {
            $repo = config('cms.release_repo', 'creativecatco/GK-CMS-Client-Starter');
            $token = null; // Public repo, no token needed
        }

        $headers = [
            'Accept' => 'application/vnd.github.v3+json',
            'User-Agent' => 'GKeys-CMS-Updater/1.0',
        ];
        if ($token) {
            $headers['Authorization'] = 'Bearer ' . $token;
        }

        // Try releases first (preferred — has release notes and download assets)
        try {
            $response = \Illuminate\Support\Facades\Http::withHeaders($headers)
                ->timeout(15)
                ->get("https://api.github.com/repos/{$repo}/releases/latest");

            if ($response->successful()) {
                $release = $response->json();
                $tag = $release['tag_name'] ?? '';
                $version = ltrim($tag, 'v');

                // For release channel, find the zip download URL
                $downloadUrl = '';
                if ($this->channel === 'release') {
                    foreach ($release['assets'] ?? [] as $asset) {
                        if (preg_match('/gkeys-cms.*\.zip$/i', $asset['name'])) {
                            $downloadUrl = $asset['browser_download_url'];
                            break;
                        }
                    }
                }

                return [
                    'version' => $version,
                    'tag' => $tag,
                    'date' => isset($release['published_at'])
                        ? \Carbon\Carbon::parse($release['published_at'])->format('M j, Y')
                        : '',
                    'notes' => $release['body'] ?? '',
                    'url' => $release['html_url'] ?? '',
                    'download_url' => $downloadUrl,
                ];
            }
        } catch (\Exception $e) {
            Log::debug('CMS Update: Releases API failed, trying tags', ['error' => $e->getMessage()]);
        }

        // Fallback: check tags
        try {
            $response = \Illuminate\Support\Facades\Http::withHeaders($headers)
                ->timeout(15)
                ->get("https://api.github.com/repos/{$repo}/tags", ['per_page' => 5]);

            if ($response->successful()) {
                $tags = $response->json();
                if (!empty($tags) && is_array($tags)) {
                    $latestTag = $tags[0];
                    $tag = $latestTag['name'] ?? '';
                    $version = ltrim($tag, 'v');

                    // Get the tag's commit date
                    $commitSha = $latestTag['commit']['sha'] ?? '';
                    $date = '';
                    if ($commitSha) {
                        try {
                            $commitResponse = \Illuminate\Support\Facades\Http::withHeaders($headers)
                                ->timeout(10)
                                ->get("https://api.github.com/repos/{$repo}/commits/{$commitSha}");
                            if ($commitResponse->successful()) {
                                $commitData = $commitResponse->json();
                                $date = isset($commitData['commit']['author']['date'])
                                    ? \Carbon\Carbon::parse($commitData['commit']['author']['date'])->format('M j, Y')
                                    : '';
                            }
                        } catch (\Exception $e) {
                            // Ignore
                        }
                    }

                    return [
                        'version' => $version,
                        'tag' => $tag,
                        'date' => $date,
                        'notes' => '',
                        'url' => "https://github.com/{$repo}/releases/tag/{$tag}",
                        'download_url' => '',
                    ];
                }
            }
        } catch (\Exception $e) {
            Log::warning('CMS Update: Tags API also failed', ['error' => $e->getMessage()]);
        }

        return null;
    }

    /**
     * Get the changelog from the package.
     */
    public function getChangelog(): string
    {
        $changelogPath = dirname(__DIR__) . '/../CHANGELOG.md';
        if (file_exists($changelogPath)) {
            return file_get_contents($changelogPath);
        }
        return '';
    }

    /**
     * Ensure auth.json exists for private repo access (composer channel only).
     */
    protected function ensureAuthJson(): void
    {
        $githubToken = Setting::get('github_token');
        if (empty($githubToken)) {
            return;
        }

        $authFile = $this->basePath . '/auth.json';
        $authData = [
            'github-oauth' => [
                'github.com' => $githubToken,
            ],
        ];

        $existingAuth = [];
        if (file_exists($authFile)) {
            $existingAuth = json_decode(file_get_contents($authFile), true) ?: [];
        }

        if (($existingAuth['github-oauth']['github.com'] ?? '') !== $githubToken) {
            file_put_contents($authFile, json_encode($authData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            @chmod($authFile, 0600);
        }
    }

    /**
     * Ensure the template hash manifest exists.
     */
    protected function ensureTemplateManifest(): void
    {
        $protection = new TemplateProtection();
        $manifest = $protection->loadManifest();

        if (empty($manifest)) {
            $protection->buildManifest();
        }
    }

    /**
     * Find the composer binary on the system.
     */
    protected function findComposer(): string
    {
        $composerPath = trim(shell_exec('which composer 2>/dev/null') ?: '');
        if (!empty($composerPath)) {
            return $composerPath;
        }

        $paths = [
            '/usr/local/bin/composer',
            '/usr/bin/composer',
            '/usr/local/bin/composer.phar',
            $this->basePath . '/composer.phar',
        ];

        foreach ($paths as $p) {
            if (file_exists($p)) {
                return $p;
            }
        }

        return 'composer'; // Hope it's in PATH
    }

    /**
     * Get the GitHub token from settings.
     */
    protected function getGithubToken(): ?string
    {
        try {
            $setting = \Illuminate\Support\Facades\DB::table('settings')
                ->where('key', 'github_token')
                ->first();

            if ($setting && !empty($setting->value)) {
                try {
                    return decrypt($setting->value);
                } catch (\Exception $e) {
                    return $setting->value;
                }
            }
        } catch (\Exception $e) {
            // Fall through
        }

        return null;
    }
}
