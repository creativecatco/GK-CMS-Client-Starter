<?php

namespace CreativeCatCo\GkCmsCore\Services\Ai\Tools;

use CreativeCatCo\GkCmsCore\Models\Page;
use CreativeCatCo\GkCmsCore\Models\Setting;

class GetCssTool extends AbstractTool
{
    public function name(): string
    {
        return 'get_css';
    }

    public function description(): string
    {
        return 'Get the current CSS for the site (global) or a specific page. Global CSS applies to all pages. Page-specific CSS only applies to that page.';
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'scope' => [
                    'type' => 'string',
                    'enum' => ['global', 'page'],
                    'description' => 'Whether to get global CSS or page-specific CSS.',
                ],
                'slug' => [
                    'type' => 'string',
                    'description' => 'The page slug (required when scope is "page").',
                ],
            ],
            'required' => ['scope'],
        ];
    }

    public function execute(array $params): array
    {
        $scope = $params['scope'] ?? 'global';

        if ($scope === 'global') {
            $css = Setting::get('global_css', '');
            return $this->success([
                'scope' => 'global',
                'css' => $css,
                'length' => strlen($css),
            ], $css ? 'Global CSS retrieved.' : 'No global CSS is currently set.');
        }

        // Page-specific CSS
        $slug = $params['slug'] ?? '';
        if (empty($slug)) {
            return $this->error('Page slug is required when scope is "page".');
        }

        $page = Page::where('slug', $slug)->first();
        if (!$page) {
            return $this->error("Page not found with slug: '{$slug}'.");
        }

        $css = $page->custom_css ?? '';
        return $this->success([
            'scope' => 'page',
            'slug' => $slug,
            'title' => $page->title,
            'css' => $css,
            'length' => strlen($css),
        ], $css ? "CSS for page '{$page->title}' retrieved." : "No custom CSS set for page '{$page->title}'.");
    }
}
