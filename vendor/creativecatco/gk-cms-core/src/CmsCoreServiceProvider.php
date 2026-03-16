<?php

namespace CreativeCatCo\GkCmsCore;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;
use CreativeCatCo\GkCmsCore\Services\SeoService;
use CreativeCatCo\GkCmsCore\Database\Seeders\DefaultContentSeeder;
use Filament\Panel;

class CmsCoreServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Merge package config
        $this->mergeConfigFrom(__DIR__ . '/../config/cms.php', 'cms');

        // Register the SEO service as a singleton
        $this->app->singleton(SeoService::class, function () {
            return new SeoService();
        });

        // Register a convenient alias
        $this->app->alias(SeoService::class, 'cms-seo');
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->registerPublishables();
        $this->registerMigrations();
        $this->registerRoutes();
        $this->registerViews();
        $this->registerPublicAssets();
        $this->ensureStorageLink();
        $this->registerStorageFallbackRoute();

        // Register artisan commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                \CreativeCatCo\GkCmsCore\Console\Commands\SeedDefaultContent::class,
                \CreativeCatCo\GkCmsCore\Console\Commands\SafePublishTemplates::class,
            ]);
        }
    }

    /**
     * Register publishable resources.
     */
    protected function registerPublishables(): void
    {
        if ($this->app->runningInConsole()) {
            // Publish config
            $this->publishes([
                __DIR__ . '/../config/cms.php' => config_path('cms.php'),
            ], 'cms-config');

            // Publish migrations
            $this->publishes([
                __DIR__ . '/../database/migrations' => database_path('migrations'),
            ], 'cms-migrations');

            // Publish views
            $this->publishes([
                __DIR__ . '/../resources/views' => resource_path('views/vendor/cms-core'),
            ], 'cms-views');

            // Publish brand assets
            $this->publishes([
                __DIR__ . '/../resources/assets/brand' => public_path('vendor/cms-core/brand'),
            ], 'cms-assets');

            // Publish image assets (icons, favicons)
            $this->publishes([
                __DIR__ . '/../resources/assets/img' => public_path('vendor/cms-core/img'),
            ], 'cms-assets');
        }
    }

    /**
     * Register package migrations.
     */
    protected function registerMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
    }

    /**
     * Register package routes.
     */
    protected function registerRoutes(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
        $this->loadRoutesFrom(__DIR__ . '/../routes/api.php');
    }

    /**
     * Register package views.
     */
    protected function registerViews(): void
    {
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'cms-core');
    }

    /**
     * Copy brand and image assets to public directory automatically.
     * Handles both standard Laravel (public/) and SiteGround (public_html/) setups.
     */
    protected function registerPublicAssets(): void
    {
        $assetMappings = [
            'brand' => 'vendor/cms-core/brand',
            'img'   => 'vendor/cms-core/img',
        ];

        // Determine all public directories to copy to
        $publicDirs = [public_path()];

        // Also copy to public_html if it exists and is different from public_path()
        $publicHtmlPath = base_path('public_html');
        if (is_dir($publicHtmlPath) && realpath($publicHtmlPath) !== realpath(public_path())) {
            $publicDirs[] = $publicHtmlPath;
        }

        foreach ($assetMappings as $assetFolder => $relativePath) {
            $source = __DIR__ . '/../resources/assets/' . $assetFolder;
            if (!is_dir($source)) {
                continue;
            }

            foreach ($publicDirs as $publicDir) {
                $destination = $publicDir . '/' . $relativePath;
                // Always sync files (not just when directory is missing)
                @mkdir($destination, 0755, true);
                $files = scandir($source);
                foreach ($files as $file) {
                    if ($file !== '.' && $file !== '..') {
                        $srcFile = $source . '/' . $file;
                        $dstFile = $destination . '/' . $file;
                        // Copy if destination doesn't exist or source is newer
                        if (!file_exists($dstFile) || filemtime($srcFile) > filemtime($dstFile)) {
                            @copy($srcFile, $dstFile);
                        }
                    }
                }
            }
        }
    }

    /**
     * Ensure the storage symlink exists in the public directory.
     * On shared hosting (SiteGround), the symlink may not exist and
     * `php artisan storage:link` may not have been run.
     */
    protected function ensureStorageLink(): void
    {
        // Don't run during console commands to avoid permission issues
        if ($this->app->runningInConsole()) {
            return;
        }

        $publicStoragePath = public_path('storage');
        $storagePath = storage_path('app/public');

        // If the symlink or directory already exists, nothing to do
        if (is_link($publicStoragePath) || is_dir($publicStoragePath)) {
            return;
        }

        // Try to create the symlink
        if (is_dir($storagePath)) {
            @symlink($storagePath, $publicStoragePath);
        }
    }

    /**
     * Register a fallback route that serves storage files directly.
     * This handles the case where symlinks don't work (some shared hosting).
     * The route only activates if the file isn't served directly by the web server.
     */
    protected function registerStorageFallbackRoute(): void
    {
        Route::middleware('web')->get('storage/{path}', function (string $path) {
            $fullPath = storage_path('app/public/' . $path);

            if (!file_exists($fullPath)) {
                abort(404);
            }

            // Security: prevent directory traversal
            $realPath = realpath($fullPath);
            $storagePath = realpath(storage_path('app/public'));

            if (!$realPath || !$storagePath || !str_starts_with($realPath, $storagePath)) {
                abort(403);
            }

            $mimeType = mime_content_type($realPath) ?: 'application/octet-stream';

            return response()->file($realPath, [
                'Content-Type' => $mimeType,
                'Cache-Control' => 'public, max-age=31536000',
            ]);
        })->where('path', '.*')->name('cms.storage.fallback');
    }
}
