<?php

namespace CreativeCatCo\GkCmsCore\Services\Ai\Tools;

use CreativeCatCo\GkCmsCore\Models\Page;
use CreativeCatCo\GkCmsCore\Models\Post;
use CreativeCatCo\GkCmsCore\Models\Portfolio;
use CreativeCatCo\GkCmsCore\Models\Product;
use CreativeCatCo\GkCmsCore\Models\Menu;
use CreativeCatCo\GkCmsCore\Models\Setting;

class GetSiteOverviewTool extends AbstractTool
{
    public function name(): string
    {
        return 'get_site_overview';
    }

    public function description(): string
    {
        return 'Get a comprehensive overview of the entire website including all pages, posts, portfolios, products, menus, theme settings, and site settings. Use this at the start of a conversation to understand the current state of the site.';
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => new \stdClass(), // No parameters
            'required' => [],
        ];
    }

    public function execute(array $params): array
    {
        $pages = Page::orderBy('sort_order')->get()->map(function ($page) {
            return [
                'id' => $page->id,
                'title' => $page->title,
                'slug' => $page->slug,
                'template' => $page->template,
                'status' => $page->status,
                'has_custom_template' => !empty($page->custom_template),
                'field_count' => is_array($page->fields) ? count($page->fields) : 0,
                'has_custom_css' => !empty($page->custom_css),
            ];
        })->toArray();

        $posts = Post::orderByDesc('created_at')->limit(20)->get()->map(function ($post) {
            return [
                'id' => $post->id,
                'title' => $post->title,
                'slug' => $post->slug,
                'status' => $post->status,
                'excerpt' => \Illuminate\Support\Str::limit($post->excerpt ?? strip_tags($post->content ?? ''), 100),
            ];
        })->toArray();

        $portfolios = Portfolio::orderByDesc('created_at')->limit(20)->get()->map(function ($item) {
            return [
                'id' => $item->id,
                'title' => $item->title,
                'slug' => $item->slug,
                'status' => $item->status,
                'client' => $item->client,
            ];
        })->toArray();

        $products = Product::orderByDesc('created_at')->limit(20)->get()->map(function ($item) {
            return [
                'id' => $item->id,
                'title' => $item->title,
                'slug' => $item->slug,
                'status' => $item->status,
                'price' => $item->price,
            ];
        })->toArray();

        $menus = Menu::all()->map(function ($menu) {
            return [
                'id' => $menu->id,
                'name' => $menu->name,
                'location' => $menu->location,
                'item_count' => is_array($menu->items) ? count($menu->items) : 0,
                'items' => $menu->items,
            ];
        })->toArray();

        // Theme settings
        $themeKeys = [
            'theme_primary_color', 'theme_secondary_color', 'theme_accent_color',
            'theme_text_color', 'theme_bg_color', 'theme_header_bg', 'theme_footer_bg',
            'theme_font_heading', 'theme_font_body',
        ];
        $theme = [];
        foreach ($themeKeys as $key) {
            $theme[$key] = Setting::get($key, '');
        }

        // Site settings
        $siteKeys = [
            'site_name', 'tagline', 'company_name', 'company_email', 'company_phone',
            'company_address', 'contact_email', 'contact_phone', 'contact_address',
            'social_facebook', 'social_twitter', 'social_instagram', 'social_linkedin',
            'social_youtube', 'social_tiktok', 'home_page_id',
            'enable_portfolio', 'enable_products',
        ];
        $siteSettings = [];
        foreach ($siteKeys as $key) {
            $val = Setting::get($key, '');
            if ($val !== '' && $val !== null) {
                $siteSettings[$key] = $val;
            }
        }

        $globalCss = Setting::get('global_css', '');

        return $this->success([
            'pages' => $pages,
            'posts' => $posts,
            'portfolios' => $portfolios,
            'products' => $products,
            'menus' => $menus,
            'theme' => $theme,
            'site_settings' => $siteSettings,
            'global_css' => $globalCss ? \Illuminate\Support\Str::limit($globalCss, 500) : '',
            'summary' => sprintf(
                '%d pages, %d posts, %d portfolios, %d products',
                count($pages),
                count($posts),
                count($portfolios),
                count($products)
            ),
        ], 'Site overview retrieved successfully.');
    }
}
