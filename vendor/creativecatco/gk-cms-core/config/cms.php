<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Site Name
    |--------------------------------------------------------------------------
    |
    | The default site name used in meta tags and the admin panel.
    |
    */
    'site_name' => env('CMS_SITE_NAME', 'My Website'),

    /*
    |--------------------------------------------------------------------------
    | Admin Path
    |--------------------------------------------------------------------------
    |
    | The URL path prefix for the Filament admin panel.
    |
    */
    'admin_path' => env('CMS_ADMIN_PATH', '/admin'),

    /*
    |--------------------------------------------------------------------------
    | Posts Per Page
    |--------------------------------------------------------------------------
    |
    | The number of blog posts to display per page on the public blog listing.
    |
    */
    'posts_per_page' => env('CMS_POSTS_PER_PAGE', 12),

    /*
    |--------------------------------------------------------------------------
    | Media Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for the media library: upload path, allowed file types,
    | maximum upload size, and storage disk.
    |
    */
    'media_upload_path' => env('CMS_MEDIA_UPLOAD_PATH', 'cms/media'),

    'media_disk' => env('CMS_MEDIA_DISK', 'public'),

    'allowed_file_types' => [
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp',
        'image/svg+xml',
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'video/mp4',
        'video/webm',
    ],

    'max_upload_size' => env('CMS_MAX_UPLOAD_SIZE', 10240), // in kilobytes (10 MB)

    /*
    |--------------------------------------------------------------------------
    | Theme
    |--------------------------------------------------------------------------
    |
    | The theme namespace used for resolving Blade views on the public site.
    | Views will be resolved as: {theme}.pages.default, {theme}.blog.index, etc.
    |
    */
    'theme' => env('CMS_THEME', 'theme'),

    /*
    |--------------------------------------------------------------------------
    | Route Prefix
    |--------------------------------------------------------------------------
    |
    | The URL prefix for the public-facing CMS routes (blog, sitemap, etc.).
    | Set to null or empty string for no prefix.
    |
    */
    'route_prefix' => env('CMS_ROUTE_PREFIX', ''),

    /*
    |--------------------------------------------------------------------------
    | Route Middleware
    |--------------------------------------------------------------------------
    |
    | Middleware to apply to the public-facing CMS routes.
    |
    */
    'route_middleware' => ['web'],

    /*
    |--------------------------------------------------------------------------
    | API Token
    |--------------------------------------------------------------------------
    |
    | Bearer token for API authentication (used by AI chatbot).
    | Set CMS_API_TOKEN in your .env file.
    |
    */
    'api_token' => env('CMS_API_TOKEN'),

    /*
    |--------------------------------------------------------------------------
    | User Roles
    |--------------------------------------------------------------------------
    |
    | Available roles for CMS users.
    |
    */
    'roles' => [
        'admin' => 'Administrator',
        'editor' => 'Editor',
        'viewer' => 'Viewer',
    ],

    /*
    |--------------------------------------------------------------------------
    | CMS Version
    |--------------------------------------------------------------------------
    |
    | The current version of GKeys CMS core package.
    |
    */
    'version' => '0.8.5.2',

    /*
    |--------------------------------------------------------------------------
    | Update Channel
    |--------------------------------------------------------------------------
    |
    | Controls how the CMS checks for and applies updates.
    |
    | 'composer' — Uses composer update (requires GitHub token and auth.json).
    |              Best for development environments.
    |
    | 'release'  — Downloads pre-built release zips from GitHub Releases.
    |              No GitHub token needed. Best for customer/production installs.
    |
    */
    'update_channel' => env('CMS_UPDATE_CHANNEL', 'release'),

    /*
    |--------------------------------------------------------------------------
    | Release Repository
    |--------------------------------------------------------------------------
    |
    | The GitHub repository that hosts the release zips for the 'release'
    | update channel. This should be the Client-Starter repo, not the core.
    |
    */
    'release_repo' => env('CMS_RELEASE_REPO', 'creativecatco/GK-CMS-Client-Starter'),

    /*
    |--------------------------------------------------------------------------
    | Auto Repair on Update
    |--------------------------------------------------------------------------
    |
    | When enabled, the CMS will automatically run a database health check
    | after each update and repair any detected issues. This is a smart
    | patch system — it only runs repairs when issues are actually found.
    |
    | Set to false to disable automatic repairs (you can still run them
    | manually via the admin panel or `php artisan cms:health --repair`).
    |
    */
    'auto_repair_on_update' => env('CMS_AUTO_REPAIR', true),

    /*
    |--------------------------------------------------------------------------
    | Branding
    |--------------------------------------------------------------------------
    |
    | GKeys CMS branding configuration.
    |
    */
    'brand' => [
        'name' => 'GKeys CMS',
        'company' => 'Growth Keys',
        'website' => 'https://growthkeys.com',
        'app_url' => 'https://gkeys.app',
    ],

];
