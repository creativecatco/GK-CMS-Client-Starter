<?php

namespace CreativeCatCo\GkCmsCore\Services\Ai\Tools;

use CreativeCatCo\GkCmsCore\Models\Page;
use CreativeCatCo\GkCmsCore\Models\Setting;

class UpdateCssTool extends AbstractTool
{
    public function name(): string
    {
        return 'update_css';
    }

    public function description(): string
    {
        return 'Update the CSS for the entire site (global) or a specific page. Global CSS applies to all pages. Page-specific CSS only applies to that page. This REPLACES the existing CSS entirely — include all desired styles.';
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'scope' => [
                    'type' => 'string',
                    'enum' => ['global', 'page'],
                    'description' => 'Whether to update global CSS or page-specific CSS.',
                ],
                'slug' => [
                    'type' => 'string',
                    'description' => 'The page slug (required when scope is "page").',
                ],
                'css' => [
                    'type' => 'string',
                    'description' => 'The complete CSS code. Use theme CSS variables (var(--color-primary), etc.) where possible for consistency.',
                ],
            ],
            'required' => ['scope', 'css'],
        ];
    }

    public function execute(array $params): array
    {
        $scope = $params['scope'] ?? 'global';
        $css = $params['css'] ?? '';

        if ($scope === 'global') {
            Setting::set('global_css', $css, 'theme');

            return $this->success([
                'scope' => 'global',
                'length' => strlen($css),
            ], 'Global CSS updated successfully. Changes apply to all pages.');
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

        try {
            $page->update(['custom_css' => $css]);

            return $this->success([
                'scope' => 'page',
                'slug' => $slug,
                'title' => $page->title,
                'length' => strlen($css),
            ], "CSS for page '{$page->title}' updated successfully.");
        } catch (\Exception $e) {
            return $this->error("Failed to update CSS: {$e->getMessage()}");
        }
    }

    public function captureRollbackData(array $params): array
    {
        $scope = $params['scope'] ?? 'global';

        if ($scope === 'global') {
            return [
                'scope' => 'global',
                'css' => Setting::get('global_css', ''),
            ];
        }

        $page = Page::where('slug', $params['slug'] ?? '')->first();
        if (!$page) {
            return [];
        }

        return [
            'scope' => 'page',
            'slug' => $page->slug,
            'css' => $page->custom_css ?? '',
        ];
    }

    public function rollback(array $rollbackData): bool
    {
        $scope = $rollbackData['scope'] ?? '';

        if ($scope === 'global') {
            Setting::set('global_css', $rollbackData['css'] ?? '', 'theme');
            return true;
        }

        if ($scope === 'page') {
            $page = Page::where('slug', $rollbackData['slug'] ?? '')->first();
            if ($page) {
                $page->update(['custom_css' => $rollbackData['css'] ?? '']);
                return true;
            }
        }

        return false;
    }
}
