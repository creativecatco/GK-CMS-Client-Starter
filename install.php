<?php
/**
 * GKeys CMS Installer
 *
 * Single-file web installer for GKeys CMS.
 * Upload this file to your web server's document root and visit it in a browser.
 *
 * @version 1.2.0
 * @author  Growth Keys / CreativeCat Co.
 */

// ─── Configuration ──────────────────────────────────────────────────────────
define('GKEYS_INSTALLER_VERSION', '1.2.0');
define('GKEYS_MIN_PHP', '8.2.0');
define('GKEYS_RELEASE_API', 'https://api.github.com/repos/creativecatco/GK-CMS-Client-Starter/releases/latest');
define('GKEYS_INSTALL_DIR', dirname(__FILE__)); // public_html (web root)

// ─── Security: Prevent re-installation ──────────────────────────────────────
$envFile = dirname(__FILE__) . '/../.env';
if (file_exists($envFile) && filesize($envFile) > 50) {
    $envContents = file_get_contents($envFile);
    if (strpos($envContents, 'APP_KEY=base64:') !== false) {
        die('<h2>GKeys CMS is already installed.</h2><p>Delete install.php to remove this message, or delete .env to reinstall.</p>');
    }
}

// ─── Handle AJAX API Requests ───────────────────────────────────────────────
if (isset($_GET['action'])) {
    header('Content-Type: application/json');

    switch ($_GET['action']) {
        case 'check_system':
            echo json_encode(checkSystem());
            exit;

        case 'test_database':
            echo json_encode(testDatabase($_POST));
            exit;

        case 'install':
            echo json_encode(runInstall($_POST));
            exit;

        case 'get_latest_version':
            echo json_encode(getLatestVersion());
            exit;

        default:
            echo json_encode(['success' => false, 'error' => 'Unknown action']);
            exit;
    }
}

// ─── System Check Functions ─────────────────────────────────────────────────

function checkSystem(): array {
    $checks = [];

    // PHP Version
    $phpOk = version_compare(PHP_VERSION, GKEYS_MIN_PHP, '>=');
    $checks[] = [
        'name' => 'PHP Version',
        'required' => '>= ' . GKEYS_MIN_PHP,
        'current' => PHP_VERSION,
        'pass' => $phpOk,
    ];

    // Required Extensions
    $requiredExts = [
        'pdo_mysql' => 'PDO MySQL',
        'mbstring' => 'Mbstring',
        'xml' => 'XML',
        'ctype' => 'Ctype',
        'json' => 'JSON',
        'bcmath' => 'BCMath',
        'curl' => 'cURL',
        'gd' => 'GD',
        'zip' => 'Zip',
        'openssl' => 'OpenSSL',
        'fileinfo' => 'Fileinfo',
        'tokenizer' => 'Tokenizer',
    ];

    foreach ($requiredExts as $ext => $label) {
        $loaded = extension_loaded($ext);
        $checks[] = [
            'name' => $label . ' Extension',
            'required' => 'Enabled',
            'current' => $loaded ? 'Enabled' : 'Missing',
            'pass' => $loaded,
        ];
    }

    // Disk Space
    $freeSpace = @disk_free_space(GKEYS_INSTALL_DIR);
    $freeSpaceMb = $freeSpace ? round($freeSpace / 1024 / 1024) : 0;
    $checks[] = [
        'name' => 'Disk Space',
        'required' => '>= 200 MB',
        'current' => $freeSpaceMb . ' MB',
        'pass' => $freeSpaceMb >= 200,
    ];

    // Directory Writable
    $writable = is_writable(GKEYS_INSTALL_DIR);
    $checks[] = [
        'name' => 'Directory Writable',
        'required' => 'Yes',
        'current' => $writable ? 'Yes' : 'No',
        'pass' => $writable,
    ];

    // Parent Directory Writable (for Laravel files)
    $parentDir = dirname(GKEYS_INSTALL_DIR);
    $parentWritable = is_writable($parentDir);
    $checks[] = [
        'name' => 'Parent Directory Writable',
        'required' => 'Yes',
        'current' => $parentWritable ? 'Yes' : 'No',
        'pass' => $parentWritable,
    ];

    // allow_url_fopen or curl
    $canDownload = ini_get('allow_url_fopen') || extension_loaded('curl');
    $checks[] = [
        'name' => 'File Download',
        'required' => 'cURL or allow_url_fopen',
        'current' => $canDownload ? 'Available' : 'Not available',
        'pass' => $canDownload,
    ];

    $allPass = true;
    foreach ($checks as $c) {
        if (!$c['pass']) { $allPass = false; break; }
    }

    return ['success' => true, 'checks' => $checks, 'all_pass' => $allPass];
}

function testDatabase(array $data): array {
    $host = $data['db_host'] ?? '127.0.0.1';
    $port = $data['db_port'] ?? '3306';
    $name = $data['db_name'] ?? '';
    $user = $data['db_user'] ?? '';
    $pass = $data['db_pass'] ?? '';

    if (empty($name) || empty($user)) {
        return ['success' => false, 'error' => 'Database name and username are required.'];
    }

    try {
        $dsn = "mysql:host={$host};port={$port};dbname={$name}";
        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 5,
        ]);

        // Check if database is empty (no tables)
        $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        $tableCount = count($tables);

        return [
            'success' => true,
            'message' => "Connected successfully! Database has {$tableCount} existing table(s).",
            'table_count' => $tableCount,
        ];
    } catch (PDOException $e) {
        $msg = $e->getMessage();
        if (strpos($msg, 'Access denied') !== false) {
            $msg = 'Access denied. Check your username and password.';
        } elseif (strpos($msg, 'Unknown database') !== false) {
            $msg = "Database '{$name}' does not exist. Create it first in your hosting control panel.";
        } elseif (strpos($msg, 'Connection refused') !== false) {
            $msg = 'Connection refused. Check the host and port.';
        }
        return ['success' => false, 'error' => $msg];
    }
}

function getLatestVersion(): array {
    $url = GKEYS_RELEASE_API;
    $context = stream_context_create([
        'http' => [
            'header' => "User-Agent: GKeys-CMS-Installer/" . GKEYS_INSTALLER_VERSION . "\r\n",
            'timeout' => 15,
        ],
    ]);

    $response = @file_get_contents($url, false, $context);
    if ($response === false) {
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 15,
                CURLOPT_HTTPHEADER => ['User-Agent: GKeys-CMS-Installer/' . GKEYS_INSTALLER_VERSION],
                CURLOPT_FOLLOWLOCATION => true,
            ]);
            $response = curl_exec($ch);
            curl_close($ch);
        }
    }

    if (empty($response)) {
        return ['success' => false, 'error' => 'Could not reach the download server.'];
    }

    $data = json_decode($response, true);
    if (empty($data) || empty($data['tag_name'])) {
        return ['success' => false, 'error' => 'No releases found.'];
    }

    // Find the zip asset
    $downloadUrl = '';
    foreach ($data['assets'] ?? [] as $asset) {
        if (preg_match('/gkeys-cms.*\.zip$/i', $asset['name'])) {
            $downloadUrl = $asset['browser_download_url'];
            break;
        }
    }

    return [
        'success' => true,
        'version' => $data['tag_name'],
        'download_url' => $downloadUrl,
        'release_notes' => $data['body'] ?? '',
    ];
}

// ─── Main Install Function ──────────────────────────────────────────────────

function runInstall(array $data): array {
    // Try to extend time limit (may not work on all hosts)
    @set_time_limit(600);
    @ini_set('memory_limit', '512M');

    $log = [];

    try {
        $installBase = dirname(GKEYS_INSTALL_DIR); // Parent of public_html

        // ─── Step 1: Download the release zip ───
        $log[] = '▸ Downloading GKeys CMS...';
        $downloadUrl = $data['download_url'] ?? '';
        if (empty($downloadUrl)) {
            return ['success' => false, 'error' => 'No download URL provided.', 'log' => $log];
        }

        $zipPath = $installBase . '/gkeys-cms-download.zip';
        $downloaded = downloadFile($downloadUrl, $zipPath);
        if (!$downloaded) {
            return ['success' => false, 'error' => 'Failed to download the release. Check your server\'s internet connectivity.', 'log' => $log];
        }
        $zipSize = filesize($zipPath);
        $log[] = '  ✓ Download complete (' . round($zipSize / 1024 / 1024, 1) . ' MB)';

        if ($zipSize < 1000000) {
            @unlink($zipPath);
            return ['success' => false, 'error' => 'Downloaded file is too small (' . round($zipSize / 1024) . ' KB). The download may have failed or been blocked.', 'log' => $log];
        }

        // ─── Step 2: Extract the zip ───
        $log[] = '▸ Extracting files...';
        $extracted = false;

        // Method 1: Use system unzip command (fastest, most memory-efficient)
        $unzipBin = trim(shell_exec('which unzip 2>/dev/null') ?? '');
        if (!empty($unzipBin) && is_executable($unzipBin)) {
            $log[] = '  Using system unzip...';
            $output = shell_exec("cd " . escapeshellarg($installBase) . " && unzip -o -q " . escapeshellarg($zipPath) . " 2>&1");
            // Verify extraction by checking for key files
            if (file_exists($installBase . '/artisan') || file_exists($installBase . '/vendor/autoload.php')) {
                $extracted = true;
                $log[] = '  ✓ Extraction complete (system unzip).';
            } else {
                $log[] = '  ⚠ System unzip ran but files not found at expected location. Output: ' . substr(trim($output ?? ''), 0, 200);
            }
        }

        // Method 2: Use PHP ZipArchive (fallback)
        if (!$extracted) {
            $log[] = '  Trying PHP ZipArchive...';
            if (!class_exists('ZipArchive')) {
                @unlink($zipPath);
                return ['success' => false, 'error' => 'Neither unzip command nor PHP ZipArchive is available. Cannot extract files.', 'log' => $log];
            }

            $zip = new ZipArchive();
            $openResult = $zip->open($zipPath);
            if ($openResult !== true) {
                @unlink($zipPath);
                return ['success' => false, 'error' => 'Failed to open zip file. Error code: ' . $openResult, 'log' => $log];
            }

            $numFiles = $zip->numFiles;
            $log[] = '  Extracting ' . $numFiles . ' files...';

            // Extract directly to installBase (zip has no wrapper directory)
            $result = $zip->extractTo($installBase);
            $zip->close();

            if (!$result) {
                @unlink($zipPath);
                return ['success' => false, 'error' => 'ZipArchive::extractTo() failed. This may be due to memory limits or disk space. Try extracting manually via SSH.', 'log' => $log];
            }

            // Verify extraction
            if (file_exists($installBase . '/artisan') || file_exists($installBase . '/vendor/autoload.php')) {
                $extracted = true;
                $log[] = '  ✓ Extraction complete (PHP ZipArchive).';
            } else {
                $log[] = '  ⚠ ZipArchive reported success but key files not found.';
            }
        }

        // Method 3: Try extracting to temp dir and moving (in case zip has a wrapper dir)
        if (!$extracted) {
            $log[] = '  Trying temp directory extraction...';
            $tempDir = $installBase . '/gkeys-cms-temp-' . time();
            @mkdir($tempDir, 0755, true);

            $zip = new ZipArchive();
            if ($zip->open($zipPath) === true) {
                $zip->extractTo($tempDir);
                $zip->close();

                // Find the source directory
                $sourceDir = $tempDir;
                if (is_dir($tempDir . '/gkeys-cms')) {
                    $sourceDir = $tempDir . '/gkeys-cms';
                } else {
                    $dirs = glob($tempDir . '/*', GLOB_ONLYDIR);
                    if (!empty($dirs) && count($dirs) === 1) {
                        $sourceDir = $dirs[0];
                    }
                }

                // Move files
                if (file_exists($sourceDir . '/artisan')) {
                    moveExtractedFiles($sourceDir, $installBase, GKEYS_INSTALL_DIR);
                    $extracted = true;
                    $log[] = '  ✓ Extraction complete (temp directory method).';
                }

                recursiveDelete($tempDir);
            }
        }

        if (!$extracted) {
            @unlink($zipPath);
            return ['success' => false, 'error' => 'All extraction methods failed. Please extract the zip manually via SSH or contact support.', 'log' => $log];
        }

        // Clean up zip file
        @unlink($zipPath);

        // ─── Step 3: Move public/ files to public_html ───
        $log[] = '▸ Setting up web directory...';
        if (is_dir($installBase . '/public')) {
            recursiveCopy($installBase . '/public', GKEYS_INSTALL_DIR);
            $log[] = '  ✓ Public files copied to web root.';
        }

        // ─── Step 4: Create ALL required directories ───
        $log[] = '▸ Creating required directories...';
        $requiredDirs = [
            $installBase . '/storage',
            $installBase . '/storage/app',
            $installBase . '/storage/app/public',
            $installBase . '/storage/app/public/cms',
            $installBase . '/storage/app/public/cms/media',
            $installBase . '/storage/framework',
            $installBase . '/storage/framework/cache',
            $installBase . '/storage/framework/cache/data',
            $installBase . '/storage/framework/sessions',
            $installBase . '/storage/framework/views',
            $installBase . '/storage/framework/testing',
            $installBase . '/storage/logs',
            $installBase . '/bootstrap/cache',
        ];

        foreach ($requiredDirs as $dir) {
            if (!is_dir($dir)) {
                mkdir($dir, 0775, true);
            }
        }

        // Create .gitignore files in storage directories
        $gitignoreContent = "*\n!.gitignore\n";
        $gitignoreDirs = [
            $installBase . '/storage/app',
            $installBase . '/storage/framework/cache',
            $installBase . '/storage/framework/sessions',
            $installBase . '/storage/framework/views',
            $installBase . '/storage/logs',
            $installBase . '/bootstrap/cache',
        ];
        foreach ($gitignoreDirs as $gDir) {
            $gFile = $gDir . '/.gitignore';
            if (!file_exists($gFile)) {
                @file_put_contents($gFile, $gitignoreContent);
            }
        }
        $log[] = '  ✓ Directories created.';

        // ─── Step 5: Set permissions ───
        $log[] = '▸ Setting file permissions...';
        @chmod($installBase . '/storage', 0775);
        @chmod($installBase . '/bootstrap/cache', 0775);
        recursiveChmod($installBase . '/storage', 0775, 0664);
        recursiveChmod($installBase . '/bootstrap/cache', 0775, 0664);
        if (file_exists($installBase . '/artisan')) {
            @chmod($installBase . '/artisan', 0755);
        }
        $log[] = '  ✓ Permissions set.';

        // ─── Step 6: Create .env file ───
        $log[] = '▸ Configuring environment...';
        $appKey = 'base64:' . base64_encode(random_bytes(32));
        $appUrl = rtrim($data['app_url'] ?? ('https://' . $_SERVER['HTTP_HOST']), '/');

        $envContent = "APP_NAME=\"" . addslashes($data['site_name'] ?? 'GKeys CMS') . "\"\n";
        $envContent .= "APP_ENV=production\n";
        $envContent .= "APP_KEY={$appKey}\n";
        $envContent .= "APP_DEBUG=false\n";
        $envContent .= "APP_URL={$appUrl}\n\n";
        $envContent .= "LOG_CHANNEL=stack\n";
        $envContent .= "LOG_DEPRECATIONS_CHANNEL=null\n";
        $envContent .= "LOG_LEVEL=error\n\n";
        $envContent .= "DB_CONNECTION=mysql\n";
        $envContent .= "DB_HOST=" . ($data['db_host'] ?? '127.0.0.1') . "\n";
        $envContent .= "DB_PORT=" . ($data['db_port'] ?? '3306') . "\n";
        $envContent .= "DB_DATABASE=" . ($data['db_name'] ?? '') . "\n";
        $envContent .= "DB_USERNAME=" . ($data['db_user'] ?? '') . "\n";
        $envContent .= "DB_PASSWORD=\"" . addslashes($data['db_pass'] ?? '') . "\"\n\n";
        $envContent .= "CACHE_DRIVER=file\n";
        $envContent .= "FILESYSTEM_DISK=local\n";
        $envContent .= "QUEUE_CONNECTION=sync\n";
        $envContent .= "SESSION_DRIVER=file\n";
        $envContent .= "SESSION_LIFETIME=120\n\n";
        $envContent .= "MAIL_MAILER=smtp\n";
        $envContent .= "MAIL_HOST=\n";
        $envContent .= "MAIL_PORT=587\n";
        $envContent .= "MAIL_USERNAME=\n";
        $envContent .= "MAIL_PASSWORD=\n";
        $envContent .= "MAIL_ENCRYPTION=tls\n";
        $envContent .= "MAIL_FROM_ADDRESS=\"noreply@" . ($_SERVER['HTTP_HOST'] ?? 'example.com') . "\"\n";
        $envContent .= "MAIL_FROM_NAME=\"\${APP_NAME}\"\n\n";
        $envContent .= "# GKeys CMS Configuration\n";
        $envContent .= "CMS_SITE_NAME=\"" . addslashes($data['site_name'] ?? 'My Website') . "\"\n";
        $envContent .= "CMS_ADMIN_PATH=/admin\n";
        $envContent .= "CMS_POSTS_PER_PAGE=12\n";
        $envContent .= "CMS_THEME=theme\n";
        $envContent .= "CMS_ROUTE_PREFIX=\n";
        $envContent .= "CMS_MEDIA_UPLOAD_PATH=cms/media\n";
        $envContent .= "CMS_MEDIA_DISK=public\n";
        $envContent .= "CMS_MAX_UPLOAD_SIZE=10240\n\n";
        $envContent .= "# Update System\n";
        $envContent .= "CMS_UPDATE_CHANNEL=release\n";
        $envContent .= "CMS_RELEASE_REPO=creativecatco/GK-CMS-Client-Starter\n";

        file_put_contents($installBase . '/.env', $envContent);
        $log[] = '  ✓ Environment configured.';

        // ─── Step 7: Fix the index.php to point to the correct paths ───
        $log[] = '▸ Configuring web entry point...';
        $indexPhp = createIndexPhp();
        file_put_contents(GKEYS_INSTALL_DIR . '/index.php', $indexPhp);

        // Create .htaccess if it doesn't exist
        if (!file_exists(GKEYS_INSTALL_DIR . '/.htaccess')) {
            file_put_contents(GKEYS_INSTALL_DIR . '/.htaccess', createHtaccess());
        }
        $log[] = '  ✓ Web entry point configured.';

        // ─── Step 8: Fix User model to implement FilamentUser ───
        $log[] = '▸ Configuring User model for admin panel access...';
        patchUserModel($installBase);
        $log[] = '  ✓ User model configured.';

        // ─── Step 9: Clear default welcome route ───
        $log[] = '▸ Configuring routes...';
        patchWebRoutes($installBase);
        $log[] = '  ✓ Routes configured.';

        // ─── Step 10: Create storage symlink ───
        $log[] = '▸ Creating storage symlink...';
        $storageLink = GKEYS_INSTALL_DIR . '/storage';
        if (!file_exists($storageLink)) {
            @symlink($installBase . '/storage/app/public', $storageLink);
        }
        $log[] = '  ✓ Storage linked.';

        // ─── Step 11: Run migrations ───
        $log[] = '▸ Running database migrations...';
        $phpBinary = PHP_BINARY ?: 'php';
        $artisan = $installBase . '/artisan';

        if (!file_exists($artisan)) {
            $log[] = '  ✗ ERROR: artisan file not found at ' . $artisan;
            return ['success' => false, 'error' => 'Laravel artisan file not found. The extraction may have failed.', 'log' => $log];
        }

        $migrationOutput = shell_exec("cd " . escapeshellarg($installBase) . " && " . escapeshellarg($phpBinary) . " " . escapeshellarg($artisan) . " migrate --force 2>&1");
        $migrationResult = trim($migrationOutput ?? '');
        if (!empty($migrationResult)) {
            // Show only the last few lines to keep log clean
            $lines = explode("\n", $migrationResult);
            $lastLines = array_slice($lines, -5);
            foreach ($lastLines as $line) {
                $log[] = '  ' . $line;
            }
        }

        // Verify migrations actually ran by checking if users table exists
        try {
            $dsn = "mysql:host=" . ($data['db_host'] ?? '127.0.0.1') . ";port=" . ($data['db_port'] ?? '3306') . ";dbname=" . ($data['db_name'] ?? '');
            $pdo = new PDO($dsn, $data['db_user'] ?? '', $data['db_pass'] ?? '', [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            ]);
            $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);

            if (!in_array('users', $tables)) {
                $log[] = '  ⚠ WARNING: Migrations may have failed. Attempting retry...';
                $retryOutput = shell_exec("cd " . escapeshellarg($installBase) . " && " . escapeshellarg($phpBinary) . " " . escapeshellarg($artisan) . " migrate --force --no-interaction 2>&1");
                $log[] = '  Retry: ' . trim($retryOutput ?? 'no output');

                $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
                if (!in_array('users', $tables)) {
                    $log[] = '  ✗ CRITICAL: Database tables could not be created. Found ' . count($tables) . ' tables.';
                    $log[] = '  Please run manually via SSH: php artisan migrate --force';
                } else {
                    $log[] = '  ✓ Database tables verified on retry (' . count($tables) . ' tables).';
                }
            } else {
                $log[] = '  ✓ Database tables verified (' . count($tables) . ' tables created).';
            }
        } catch (Exception $e) {
            $log[] = '  ⚠ Could not verify tables: ' . $e->getMessage();
        }

        // ─── Step 12: Seed default content ───
        $log[] = '▸ Creating default content...';
        $seedOutput = shell_exec("cd " . escapeshellarg($installBase) . " && " . escapeshellarg($phpBinary) . " " . escapeshellarg($artisan) . " gkeys:seed-content 2>&1");
        $seedResult = trim($seedOutput ?? '');
        if (!empty($seedResult)) {
            $log[] = '  ' . $seedResult;
        } else {
            $log[] = '  ✓ Default content created.';
        }

        // ─── Step 13: Create admin user ───
        $log[] = '▸ Creating admin account...';
        $adminName = $data['admin_name'] ?? 'Admin';
        $adminEmail = $data['admin_email'] ?? 'admin@example.com';
        $adminPassword = $data['admin_password'] ?? '';

        if (!empty($adminEmail) && !empty($adminPassword)) {
            try {
                $dsn = "mysql:host=" . ($data['db_host'] ?? '127.0.0.1') . ";port=" . ($data['db_port'] ?? '3306') . ";dbname=" . ($data['db_name'] ?? '');
                $pdo = new PDO($dsn, $data['db_user'] ?? '', $data['db_pass'] ?? '', [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                ]);

                $hashedPassword = password_hash($adminPassword, PASSWORD_BCRYPT);
                $now = date('Y-m-d H:i:s');

                $stmt = $pdo->prepare(
                    "INSERT INTO users (name, email, password, role, email_verified_at, created_at, updated_at)
                     VALUES (:name, :email, :password, 'admin', :verified, :created, :updated)
                     ON DUPLICATE KEY UPDATE password = VALUES(password), role = 'admin', email_verified_at = VALUES(email_verified_at)"
                );
                $stmt->execute([
                    ':name' => $adminName,
                    ':email' => $adminEmail,
                    ':password' => $hashedPassword,
                    ':verified' => $now,
                    ':created' => $now,
                    ':updated' => $now,
                ]);
                $log[] = '  ✓ Admin account created for: ' . $adminEmail;
            } catch (Exception $e) {
                $log[] = '  ⚠ Could not create admin: ' . $e->getMessage();
                $log[] = '  You can register at ' . $appUrl . '/admin after installation.';
            }
        }

        // ─── Step 14: Set home page default ───
        $log[] = '▸ Setting default home page...';
        try {
            $dsn = "mysql:host=" . ($data['db_host'] ?? '127.0.0.1') . ";port=" . ($data['db_port'] ?? '3306') . ";dbname=" . ($data['db_name'] ?? '');
            $pdo = new PDO($dsn, $data['db_user'] ?? '', $data['db_pass'] ?? '', [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            ]);

            $stmt = $pdo->prepare("SELECT id FROM pages WHERE slug = 'home' OR title = 'Home' LIMIT 1");
            $stmt->execute();
            $homePage = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($homePage) {
                $stmt = $pdo->prepare(
                    "INSERT INTO settings (`key`, `value`, created_at, updated_at)
                     VALUES ('home_page', :page_id, NOW(), NOW())
                     ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)"
                );
                $stmt->execute([':page_id' => $homePage['id']]);
                $log[] = '  ✓ Home page set to page ID ' . $homePage['id'];
            } else {
                $log[] = '  ⚠ No "Home" page found. Set the home page in Settings after login.';
            }
        } catch (Exception $e) {
            $log[] = '  ⚠ Could not set home page: ' . $e->getMessage();
        }

        // ─── Step 15: Publish assets and clear caches ───
        $log[] = '▸ Publishing assets...';
        shell_exec("cd " . escapeshellarg($installBase) . " && " . escapeshellarg($phpBinary) . " " . escapeshellarg($artisan) . " vendor:publish --tag=cms-assets --force 2>&1");
        shell_exec("cd " . escapeshellarg($installBase) . " && " . escapeshellarg($phpBinary) . " " . escapeshellarg($artisan) . " cms:safe-publish-templates 2>&1");

        // Copy published assets to public_html too
        if (is_dir($installBase . '/public/vendor')) {
            recursiveCopy($installBase . '/public/vendor', GKEYS_INSTALL_DIR . '/vendor');
        }
        if (is_dir($installBase . '/public/css')) {
            recursiveCopy($installBase . '/public/css', GKEYS_INSTALL_DIR . '/css');
        }
        if (is_dir($installBase . '/public/js')) {
            recursiveCopy($installBase . '/public/js', GKEYS_INSTALL_DIR . '/js');
        }
        if (is_dir($installBase . '/public/build')) {
            recursiveCopy($installBase . '/public/build', GKEYS_INSTALL_DIR . '/build');
        }
        $log[] = '  ✓ Assets published.';

        $log[] = '▸ Clearing caches...';
        shell_exec("cd " . escapeshellarg($installBase) . " && " . escapeshellarg($phpBinary) . " " . escapeshellarg($artisan) . " cache:clear 2>&1");
        shell_exec("cd " . escapeshellarg($installBase) . " && " . escapeshellarg($phpBinary) . " " . escapeshellarg($artisan) . " config:clear 2>&1");
        shell_exec("cd " . escapeshellarg($installBase) . " && " . escapeshellarg($phpBinary) . " " . escapeshellarg($artisan) . " view:clear 2>&1");
        shell_exec("cd " . escapeshellarg($installBase) . " && " . escapeshellarg($phpBinary) . " " . escapeshellarg($artisan) . " route:clear 2>&1");
        $log[] = '  ✓ Caches cleared.';

        // ─── Step 16: Final cleanup ───
        $log[] = '▸ Cleaning up...';
        // Remove any leftover temp directories
        foreach (glob($installBase . '/gkeys-cms-temp-*') as $tempDir) {
            if (is_dir($tempDir)) {
                recursiveDelete($tempDir);
            }
        }
        // Remove the download zip if still present
        if (file_exists($zipPath)) {
            @unlink($zipPath);
        }
        $log[] = '  ✓ Temporary files removed.';

        // ─── Step 17: Final verification ───
        $log[] = '▸ Verifying installation...';
        $verifyErrors = [];
        if (!file_exists($installBase . '/vendor/autoload.php')) $verifyErrors[] = 'vendor/autoload.php missing';
        if (!file_exists($installBase . '/artisan')) $verifyErrors[] = 'artisan missing';
        if (!file_exists($installBase . '/.env')) $verifyErrors[] = '.env missing';
        if (!is_dir($installBase . '/storage/framework/views')) $verifyErrors[] = 'storage/framework/views missing';
        if (!is_dir($installBase . '/bootstrap/cache')) $verifyErrors[] = 'bootstrap/cache missing';
        if (!file_exists(GKEYS_INSTALL_DIR . '/index.php')) $verifyErrors[] = 'public_html/index.php missing';

        if (!empty($verifyErrors)) {
            $log[] = '  ⚠ Verification warnings: ' . implode(', ', $verifyErrors);
        } else {
            $log[] = '  ✓ All core files verified.';
        }

        // ─── Done ───
        $log[] = '';
        $log[] = '✓ Installation complete!';
        $adminUrl = $appUrl . '/admin';

        return [
            'success' => true,
            'message' => 'GKeys CMS has been installed successfully!',
            'admin_url' => $adminUrl,
            'log' => $log,
        ];

    } catch (Exception $e) {
        $log[] = '✗ ERROR: ' . $e->getMessage();
        return ['success' => false, 'error' => $e->getMessage(), 'log' => $log];
    }
}

// ─── Helper Functions ───────────────────────────────────────────────────────

function downloadFile(string $url, string $dest): bool {
    // Try curl first (handles redirects better)
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        $fp = fopen($dest, 'w');
        curl_setopt_array($ch, [
            CURLOPT_FILE => $fp,
            CURLOPT_TIMEOUT => 600,
            CURLOPT_CONNECTTIMEOUT => 30,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_HTTPHEADER => ['User-Agent: GKeys-CMS-Installer/' . GKEYS_INSTALLER_VERSION],
        ]);
        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        fclose($fp);

        if ($result && $httpCode === 200 && filesize($dest) > 1000) {
            return true;
        }
        @unlink($dest);
    }

    // Fallback to file_get_contents
    $context = stream_context_create([
        'http' => [
            'header' => "User-Agent: GKeys-CMS-Installer/" . GKEYS_INSTALLER_VERSION . "\r\n",
            'timeout' => 600,
            'follow_location' => true,
        ],
    ]);
    $data = @file_get_contents($url, false, $context);
    if ($data !== false && strlen($data) > 1000) {
        file_put_contents($dest, $data);
        return true;
    }

    return false;
}

function moveExtractedFiles(string $sourceDir, string $installBase, string $publicHtml): void {
    // Move Laravel app directories
    $appDirs = ['app', 'bootstrap', 'config', 'database', 'resources', 'routes', 'storage', 'vendor', 'tests'];
    foreach ($appDirs as $dir) {
        if (is_dir($sourceDir . '/' . $dir)) {
            $destDir = $installBase . '/' . $dir;
            if (is_dir($destDir)) {
                recursiveCopy($sourceDir . '/' . $dir, $destDir);
            } else {
                rename($sourceDir . '/' . $dir, $destDir);
            }
        }
    }

    // Move root files
    $rootFiles = ['artisan', 'composer.json', 'composer.lock', '.env.example', '.editorconfig', 'package.json', 'phpunit.xml', 'vite.config.js'];
    foreach ($rootFiles as $file) {
        if (file_exists($sourceDir . '/' . $file)) {
            copy($sourceDir . '/' . $file, $installBase . '/' . $file);
        }
    }

    // Move public directory contents to public_html
    if (is_dir($sourceDir . '/public')) {
        recursiveCopy($sourceDir . '/public', $publicHtml);
    }
}

function recursiveCopy(string $src, string $dst): void {
    if (!is_dir($dst)) {
        mkdir($dst, 0755, true);
    }
    $dir = opendir($src);
    while (($file = readdir($dir)) !== false) {
        if ($file === '.' || $file === '..') continue;
        $srcPath = $src . '/' . $file;
        $dstPath = $dst . '/' . $file;
        if (is_dir($srcPath)) {
            recursiveCopy($srcPath, $dstPath);
        } else {
            copy($srcPath, $dstPath);
        }
    }
    closedir($dir);
}

function recursiveDelete(string $dir): void {
    if (!is_dir($dir)) return;
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach ($files as $file) {
        if ($file->isDir()) {
            @rmdir($file->getRealPath());
        } else {
            @unlink($file->getRealPath());
        }
    }
    @rmdir($dir);
}

function recursiveChmod(string $dir, int $dirPerm, int $filePerm): void {
    if (!is_dir($dir)) return;
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );
    foreach ($iterator as $item) {
        if ($item->isDir()) {
            @chmod($item->getRealPath(), $dirPerm);
        } else {
            @chmod($item->getRealPath(), $filePerm);
        }
    }
}

/**
 * Patch the User model to implement FilamentUser interface.
 */
function patchUserModel(string $installBase): void {
    $userModelPath = $installBase . '/app/Models/User.php';
    if (!file_exists($userModelPath)) return;

    $content = file_get_contents($userModelPath);

    // Skip if already patched
    if (strpos($content, 'FilamentUser') !== false) return;

    // Add the FilamentUser import and interface
    $content = str_replace(
        'use Illuminate\\Database\\Eloquent\\Factories\\HasFactory;',
        "use Filament\\Models\\Contracts\\FilamentUser;\nuse Filament\\Panel;\nuse Illuminate\\Database\\Eloquent\\Factories\\HasFactory;",
        $content
    );

    $content = str_replace(
        'class User extends Authenticatable',
        'class User extends Authenticatable implements FilamentUser',
        $content
    );

    // Add canAccessPanel method before the closing brace
    $closingBrace = strrpos($content, '}');
    if ($closingBrace !== false) {
        $method = "\n    /**\n     * Determine if the user can access the Filament admin panel.\n     */\n    public function canAccessPanel(Panel \$panel): bool\n    {\n        return true;\n    }\n";
        $content = substr($content, 0, $closingBrace) . $method . substr($content, $closingBrace);
    }

    file_put_contents($userModelPath, $content);
}

/**
 * Remove the default Laravel welcome route.
 */
function patchWebRoutes(string $installBase): void {
    $routesPath = $installBase . '/routes/web.php';
    if (!file_exists($routesPath)) return;

    $content = file_get_contents($routesPath);

    if (strpos($content, "return view('welcome')") !== false) {
        $content = preg_replace(
            '/Route::get\s*\(\s*[\'"]\/[\'"]\s*,\s*function\s*\(\)\s*\{[^}]*return\s+view\s*\(\s*[\'"]welcome[\'"]\s*\)\s*;[^}]*\}\s*\)\s*;/s',
            "// CMS routes are handled by the GKeys CMS core package.\n// Add any custom routes here that should not be managed by the CMS.",
            $content
        );
        file_put_contents($routesPath, $content);
    }
}

function createIndexPhp(): string {
    return <<<'PHP'
<?php

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Determine the project root (parent of public_html)
$projectRoot = dirname(__DIR__);

/*
|--------------------------------------------------------------------------
| Check If The Application Is Under Maintenance
|--------------------------------------------------------------------------
*/
if (file_exists($maintenance = $projectRoot . '/storage/framework/maintenance.php')) {
    require $maintenance;
}

/*
|--------------------------------------------------------------------------
| Register The Auto Loader
|--------------------------------------------------------------------------
*/
require $projectRoot . '/vendor/autoload.php';

/*
|--------------------------------------------------------------------------
| Run The Application
|--------------------------------------------------------------------------
*/
$app = require_once $projectRoot . '/bootstrap/app.php';

$kernel = $app->make(Kernel::class);

$response = $kernel->handle(
    $request = Request::capture()
)->send();

$kernel->terminate($request, $response);
PHP;
}

function createHtaccess(): string {
    return <<<'HTACCESS'
<IfModule mod_rewrite.c>
    <IfModule mod_negotiation.c>
        Options -MultiViews -Indexes
    </IfModule>

    RewriteEngine On

    # Handle Authorization Header
    RewriteCond %{HTTP:Authorization} .
    RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]

    # Redirect Trailing Slashes If Not A Folder...
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_URI} (.+)/$
    RewriteRule ^ %1 [L,R=301]

    # Send Requests To Front Controller...
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^ index.php [L]
</IfModule>
HTACCESS;
}

// ─── HTML Frontend ──────────────────────────────────────────────────────────
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Install GKeys CMS</title>
    <style>
        :root {
            --primary: #cfff2e;
            --primary-dark: #a8d400;
            --bg-dark: #0f1117;
            --bg-card: #1a1d27;
            --bg-input: #252833;
            --text: #e4e4e7;
            --text-muted: #9ca3af;
            --border: #2e3140;
            --success: #22c55e;
            --error: #ef4444;
            --warning: #f59e0b;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: var(--bg-dark);
            color: var(--text);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: flex-start;
            padding: 2rem 1rem;
        }

        .installer {
            max-width: 640px;
            width: 100%;
        }

        .logo {
            text-align: center;
            margin-bottom: 2rem;
        }

        .logo h1 {
            font-size: 2rem;
            font-weight: 800;
            color: var(--primary);
            letter-spacing: -0.02em;
        }

        .logo p {
            color: var(--text-muted);
            font-size: 0.875rem;
            margin-top: 0.25rem;
        }

        /* Progress Steps */
        .steps {
            display: flex;
            justify-content: center;
            gap: 0.5rem;
            margin-bottom: 2rem;
        }

        .step-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: var(--border);
            transition: all 0.3s;
        }

        .step-dot.active {
            background: var(--primary);
            box-shadow: 0 0 12px rgba(207, 255, 46, 0.4);
        }

        .step-dot.done {
            background: var(--success);
        }

        /* Card */
        .card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 2rem;
            margin-bottom: 1rem;
        }

        .card h2 {
            font-size: 1.25rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .card p.desc {
            color: var(--text-muted);
            font-size: 0.875rem;
            margin-bottom: 1.5rem;
            line-height: 1.5;
        }

        /* Form */
        .form-group {
            margin-bottom: 1.25rem;
        }

        .form-group label {
            display: block;
            font-size: 0.8125rem;
            font-weight: 600;
            color: var(--text-muted);
            margin-bottom: 0.375rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .form-group input {
            width: 100%;
            padding: 0.625rem 0.875rem;
            background: var(--bg-input);
            border: 1px solid var(--border);
            border-radius: 8px;
            color: var(--text);
            font-size: 0.9375rem;
            outline: none;
            transition: border-color 0.2s;
        }

        .form-group input:focus {
            border-color: var(--primary);
        }

        .form-group input::placeholder {
            color: #555;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        /* Buttons */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-size: 0.9375rem;
            font-weight: 600;
            border: none;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
        }

        .btn-primary {
            background: var(--primary);
            color: #000;
        }

        .btn-primary:hover {
            background: var(--primary-dark);
        }

        .btn-primary:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .btn-secondary {
            background: var(--bg-input);
            color: var(--text);
            border: 1px solid var(--border);
        }

        .btn-secondary:hover {
            background: var(--border);
        }

        .btn-group {
            display: flex;
            justify-content: space-between;
            gap: 1rem;
            margin-top: 1.5rem;
        }

        /* Check Table */
        .check-table {
            width: 100%;
            border-collapse: collapse;
        }

        .check-table tr {
            border-bottom: 1px solid var(--border);
        }

        .check-table tr:last-child {
            border-bottom: none;
        }

        .check-table td {
            padding: 0.625rem 0;
            font-size: 0.875rem;
        }

        .check-table td:first-child {
            font-weight: 500;
        }

        .check-table td:nth-child(2) {
            color: var(--text-muted);
            text-align: center;
        }

        .check-table td:last-child {
            text-align: right;
            font-weight: 600;
        }

        .pass { color: var(--success); }
        .fail { color: var(--error); }

        /* Alert */
        .alert {
            padding: 0.75rem 1rem;
            border-radius: 8px;
            font-size: 0.875rem;
            margin-bottom: 1rem;
        }

        .alert-success {
            background: rgba(34, 197, 94, 0.1);
            border: 1px solid rgba(34, 197, 94, 0.3);
            color: var(--success);
        }

        .alert-error {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: var(--error);
        }

        .alert-warning {
            background: rgba(245, 158, 11, 0.1);
            border: 1px solid rgba(245, 158, 11, 0.3);
            color: var(--warning);
        }

        /* Log Output */
        .log-output {
            background: #000;
            border-radius: 8px;
            padding: 1rem;
            font-family: 'SF Mono', 'Fira Code', monospace;
            font-size: 0.8125rem;
            line-height: 1.6;
            color: var(--success);
            max-height: 400px;
            overflow-y: auto;
            white-space: pre-wrap;
        }

        /* Spinner */
        .spinner {
            display: inline-block;
            width: 16px;
            height: 16px;
            border: 2px solid rgba(0,0,0,0.2);
            border-top-color: #000;
            border-radius: 50%;
            animation: spin 0.6s linear infinite;
            margin-right: 0.5rem;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* Success Screen */
        .success-icon {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: rgba(34, 197, 94, 0.15);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
        }

        .success-icon svg {
            width: 40px;
            height: 40px;
            color: var(--success);
        }

        .text-center { text-align: center; }

        /* Hide steps */
        .step-content { display: none; }
        .step-content.active { display: block; }

        /* Footer */
        .footer {
            text-align: center;
            color: var(--text-muted);
            font-size: 0.75rem;
            margin-top: 2rem;
        }
    </style>
</head>
<body>
    <div class="installer">
        <div class="logo">
            <h1>GKeys CMS</h1>
            <p>Installation Wizard</p>
        </div>

        <div class="steps">
            <div class="step-dot active" id="dot-1"></div>
            <div class="step-dot" id="dot-2"></div>
            <div class="step-dot" id="dot-3"></div>
            <div class="step-dot" id="dot-4"></div>
            <div class="step-dot" id="dot-5"></div>
        </div>

        <!-- Step 1: System Check -->
        <div class="step-content active" id="step-1">
            <div class="card">
                <h2>System Requirements</h2>
                <p class="desc">Checking that your server meets the requirements for GKeys CMS.</p>
                <div id="system-checks">
                    <p style="color: var(--text-muted);">Checking system requirements...</p>
                </div>
                <div class="btn-group">
                    <div></div>
                    <button class="btn btn-primary" id="btn-step1-next" disabled onclick="goToStep(2)">Continue</button>
                </div>
            </div>
        </div>

        <!-- Step 2: Database -->
        <div class="step-content" id="step-2">
            <div class="card">
                <h2>Database Configuration</h2>
                <p class="desc">Enter your MySQL database credentials. You can create a database through your hosting control panel (cPanel, Plesk, etc.).</p>
                <div id="db-alert"></div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Database Host</label>
                        <input type="text" id="db_host" value="localhost" placeholder="localhost">
                    </div>
                    <div class="form-group">
                        <label>Port</label>
                        <input type="text" id="db_port" value="3306" placeholder="3306">
                    </div>
                </div>
                <div class="form-group">
                    <label>Database Name</label>
                    <input type="text" id="db_name" placeholder="gkeys_cms">
                </div>
                <div class="form-group">
                    <label>Database Username</label>
                    <input type="text" id="db_user" placeholder="root">
                </div>
                <div class="form-group">
                    <label>Database Password</label>
                    <input type="password" id="db_pass" placeholder="Enter password">
                </div>
                <div class="btn-group">
                    <button class="btn btn-secondary" onclick="goToStep(1)">Back</button>
                    <div style="display:flex;gap:0.5rem;">
                        <button class="btn btn-secondary" id="btn-test-db" onclick="testDatabase()">Test Connection</button>
                        <button class="btn btn-primary" id="btn-step2-next" disabled onclick="goToStep(3)">Continue</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Step 3: Admin Account -->
        <div class="step-content" id="step-3">
            <div class="card">
                <h2>Admin Account</h2>
                <p class="desc">Create your administrator account for the CMS dashboard.</p>
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" id="admin_name" placeholder="John Doe">
                </div>
                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" id="admin_email" placeholder="admin@example.com">
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" id="admin_password" placeholder="Minimum 8 characters">
                </div>
                <div class="btn-group">
                    <button class="btn btn-secondary" onclick="goToStep(2)">Back</button>
                    <button class="btn btn-primary" id="btn-step3-next" onclick="validateAdmin()">Continue</button>
                </div>
            </div>
        </div>

        <!-- Step 4: Site Info -->
        <div class="step-content" id="step-4">
            <div class="card">
                <h2>Site Information</h2>
                <p class="desc">Basic information about the website being built.</p>
                <div class="form-group">
                    <label>Site Name</label>
                    <input type="text" id="site_name" placeholder="My Awesome Website">
                </div>
                <div class="form-group">
                    <label>Site URL</label>
                    <input type="text" id="app_url" placeholder="https://example.com">
                </div>
                <div class="btn-group">
                    <button class="btn btn-secondary" onclick="goToStep(3)">Back</button>
                    <button class="btn btn-primary" onclick="startInstall()">Install GKeys CMS</button>
                </div>
            </div>
        </div>

        <!-- Step 5: Installing / Complete -->
        <div class="step-content" id="step-5">
            <div class="card" id="installing-card">
                <h2>Installing GKeys CMS</h2>
                <p class="desc">Please wait while the CMS is being installed. This may take a few minutes depending on your server speed.</p>
                <div class="log-output" id="install-log">Preparing installation...</div>
            </div>
            <div class="card" id="complete-card" style="display:none;">
                <div class="text-center">
                    <div class="success-icon">
                        <svg fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                        </svg>
                    </div>
                    <h2>Installation Complete!</h2>
                    <p class="desc" style="margin-top:0.5rem;">GKeys CMS has been installed successfully. You can now access your admin panel.</p>
                    <a class="btn btn-primary" id="admin-link" href="/admin" style="margin-top:1rem;display:inline-flex;">Go to Admin Panel</a>
                    <p style="margin-top:1.5rem;font-size:0.75rem;color:var(--warning);">
                        Important: Delete this install.php file from your server for security.
                    </p>
                </div>
            </div>
        </div>

        <div class="footer">
            GKeys CMS Installer v<?php echo GKEYS_INSTALLER_VERSION; ?> &middot; Growth Keys
        </div>
    </div>

    <script>
    var currentStep = 1;
    var downloadUrl = '';
    var latestVersion = '';

    // Auto-detect site URL
    document.getElementById('app_url').value = window.location.protocol + '//' + window.location.host;

    // Run system check on load
    document.addEventListener('DOMContentLoaded', function() {
        checkSystem();
    });

    function goToStep(step) {
        document.getElementById('step-' + currentStep).classList.remove('active');
        document.getElementById('step-' + step).classList.add('active');

        for (var i = 1; i <= 5; i++) {
            var dot = document.getElementById('dot-' + i);
            dot.classList.remove('active', 'done');
            if (i < step) dot.classList.add('done');
            if (i === step) dot.classList.add('active');
        }

        currentStep = step;
    }

    function checkSystem() {
        fetch('?action=check_system')
            .then(function(r) { return r.json(); })
            .then(function(data) {
                var html = '<table class="check-table">';
                data.checks.forEach(function(c) {
                    html += '<tr>';
                    html += '<td>' + c.name + '</td>';
                    html += '<td>' + c.required + '</td>';
                    html += '<td class="' + (c.pass ? 'pass' : 'fail') + '">' + c.current + '</td>';
                    html += '</tr>';
                });
                html += '</table>';
                document.getElementById('system-checks').innerHTML = html;

                if (data.all_pass) {
                    document.getElementById('btn-step1-next').disabled = false;
                    fetchLatestVersion();
                } else {
                    document.getElementById('system-checks').innerHTML += '<div class="alert alert-error" style="margin-top:1rem;">Some requirements are not met. Please contact your hosting provider.</div>';
                }
            })
            .catch(function(err) {
                document.getElementById('system-checks').innerHTML = '<div class="alert alert-error">Failed to check system requirements: ' + err.message + '</div>';
            });
    }

    function fetchLatestVersion() {
        fetch('?action=get_latest_version')
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.success) {
                    latestVersion = data.version;
                    downloadUrl = data.download_url;
                    if (!downloadUrl) {
                        document.getElementById('system-checks').innerHTML += '<div class="alert alert-warning" style="margin-top:1rem;">No release package found. Please contact support.</div>';
                        document.getElementById('btn-step1-next').disabled = true;
                    }
                }
            });
    }

    function testDatabase() {
        var btn = document.getElementById('btn-test-db');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner"></span> Testing...';
        document.getElementById('db-alert').innerHTML = '';

        var formData = new FormData();
        formData.append('db_host', document.getElementById('db_host').value);
        formData.append('db_port', document.getElementById('db_port').value);
        formData.append('db_name', document.getElementById('db_name').value);
        formData.append('db_user', document.getElementById('db_user').value);
        formData.append('db_pass', document.getElementById('db_pass').value);

        fetch('?action=test_database', { method: 'POST', body: formData })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                btn.disabled = false;
                btn.textContent = 'Test Connection';

                if (data.success) {
                    document.getElementById('db-alert').innerHTML = '<div class="alert alert-success">' + data.message + '</div>';
                    document.getElementById('btn-step2-next').disabled = false;
                } else {
                    document.getElementById('db-alert').innerHTML = '<div class="alert alert-error">' + data.error + '</div>';
                    document.getElementById('btn-step2-next').disabled = true;
                }
            })
            .catch(function(err) {
                btn.disabled = false;
                btn.textContent = 'Test Connection';
                document.getElementById('db-alert').innerHTML = '<div class="alert alert-error">Request failed: ' + err.message + '</div>';
            });
    }

    function validateAdmin() {
        var name = document.getElementById('admin_name').value.trim();
        var email = document.getElementById('admin_email').value.trim();
        var password = document.getElementById('admin_password').value;

        if (!name || !email || !password) {
            alert('Please fill in all fields.');
            return;
        }
        if (password.length < 8) {
            alert('Password must be at least 8 characters.');
            return;
        }
        if (!/\S+@\S+\.\S+/.test(email)) {
            alert('Please enter a valid email address.');
            return;
        }

        goToStep(4);
    }

    function startInstall() {
        var siteName = document.getElementById('site_name').value.trim();
        var appUrl = document.getElementById('app_url').value.trim();

        if (!siteName) {
            alert('Please enter a site name.');
            return;
        }

        goToStep(5);

        var formData = new FormData();
        formData.append('download_url', downloadUrl);
        formData.append('db_host', document.getElementById('db_host').value);
        formData.append('db_port', document.getElementById('db_port').value);
        formData.append('db_name', document.getElementById('db_name').value);
        formData.append('db_user', document.getElementById('db_user').value);
        formData.append('db_pass', document.getElementById('db_pass').value);
        formData.append('admin_name', document.getElementById('admin_name').value);
        formData.append('admin_email', document.getElementById('admin_email').value);
        formData.append('admin_password', document.getElementById('admin_password').value);
        formData.append('site_name', siteName);
        formData.append('app_url', appUrl);

        var logEl = document.getElementById('install-log');
        logEl.textContent = 'Starting installation...\n';

        fetch('?action=install', { method: 'POST', body: formData })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.log) {
                    logEl.textContent = data.log.join('\n');
                }

                if (data.success) {
                    document.getElementById('installing-card').style.display = 'none';
                    document.getElementById('complete-card').style.display = 'block';
                    if (data.admin_url) {
                        document.getElementById('admin-link').href = data.admin_url;
                    }
                } else {
                    logEl.textContent += '\n\n✗ ERROR: ' + (data.error || 'Installation failed.');
                    logEl.style.color = 'var(--error)';
                }
            })
            .catch(function(err) {
                logEl.textContent += '\n\n✗ ERROR: ' + err.message + '\n\nThis may be caused by a timeout. If the installation was partially completed, try accessing /admin directly.';
                logEl.style.color = 'var(--error)';
            });
    }
    </script>
</body>
</html>
