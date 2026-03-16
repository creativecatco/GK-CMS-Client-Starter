<?php

use Illuminate\Support\Facades\Route;
use CreativeCatCo\GkCmsCore\Http\Controllers\PageController;
use CreativeCatCo\GkCmsCore\Http\Controllers\PostController;
use CreativeCatCo\GkCmsCore\Http\Controllers\PortfolioController;
use CreativeCatCo\GkCmsCore\Http\Controllers\ProductController;
use CreativeCatCo\GkCmsCore\Http\Controllers\SitemapController;

/*
|--------------------------------------------------------------------------
| CMS Core Public Routes
|--------------------------------------------------------------------------
|
| These routes handle the public-facing pages, blog, portfolio, products,
| and sitemap. They are registered with the middleware defined in
| config('cms.route_middleware').
|
*/


Route::middleware(['web', 'auth'])->get('_clear-cache', function() {
    \Illuminate\Support\Facades\Artisan::call('cache:clear');
    \Illuminate\Support\Facades\Artisan::call('view:clear');
    \Illuminate\Support\Facades\Artisan::call('config:clear');
    \Illuminate\Support\Facades\Artisan::call('route:clear');
    if (function_exists('opcache_reset')) { opcache_reset(); }
    return 'All caches cleared at ' . now();
});

Route::middleware(array_merge(
    config('cms.route_middleware', ['web']),
    [\CreativeCatCo\GkCmsCore\Http\Middleware\SecurityHeaders::class]
))
    ->prefix(config('cms.route_prefix', ''))
    ->group(function () {

        // XML Sitemap
        Route::get('sitemap.xml', [SitemapController::class, 'index'])
            ->name('cms.sitemap');

        // Blog routes
        Route::get('blog', [PostController::class, 'index'])
            ->name('cms.blog.index');

        Route::get('blog/{slug}', [PostController::class, 'show'])
            ->name('cms.blog.show');

        // Portfolio routes (only if enabled)
        Route::get('portfolio', [PortfolioController::class, 'index'])
            ->name('cms.portfolio.index');

        Route::get('portfolio/{slug}', [PortfolioController::class, 'show'])
            ->name('cms.portfolio.show');

        // Product routes (only if enabled)
        Route::get('products', [ProductController::class, 'index'])
            ->name('cms.products.index');

        Route::get('products/{slug}', [ProductController::class, 'show'])
            ->name('cms.products.show');

        // Homepage route — uses home_page_id setting or falls back to slug 'home'
        Route::get('/', [PageController::class, 'showHome'])
            ->name('cms.page.home');

        // Page routes (catch-all — must be registered last)
        Route::get('{slug}', [PageController::class, 'show'])
            ->where('slug', '^(?!admin|gk-admin|livewire|filament|storage).*$')
            ->name('cms.page.show');
    });
