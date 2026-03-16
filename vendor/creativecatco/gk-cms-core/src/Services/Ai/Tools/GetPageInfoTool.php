<?php

namespace CreativeCatCo\GkCmsCore\Services\Ai\Tools;

use CreativeCatCo\GkCmsCore\Models\Page;

class GetPageInfoTool extends AbstractTool
{
    public function name(): string
    {
        return 'get_page_info';
    }

    public function description(): string
    {
        return 'Get detailed information about a specific page, including its template code, fields, CSS, and settings. Use this to understand a page before modifying it.';
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'slug' => [
                    'type' => 'string',
                    'description' => 'The URL slug of the page to retrieve (e.g., "about", "contact", "home").',
                ],
            ],
            'required' => ['slug'],
        ];
    }

    public function execute(array $params): array
    {
        $slug = $params['slug'] ?? '';

        $page = Page::where('slug', $slug)->first();
        if (!$page) {
            return $this->error("Page not found with slug: '{$slug}'. Use list_pages to see available pages.");
        }

        return $this->success([
            'id' => $page->id,
            'title' => $page->title,
            'slug' => $page->slug,
            'page_type' => $page->page_type,
            'template' => $page->template,
            'custom_template' => $page->custom_template ?? '',
            'fields' => $page->fields ?? [],
            'field_definitions' => $page->field_definitions ?? [],
            'custom_css' => $page->custom_css ?? '',
            'featured_image' => $page->featured_image,
            'status' => $page->status,
            'sort_order' => $page->sort_order,
            'seo_title' => $page->seo_title,
            'seo_description' => $page->seo_description,
        ], "Page '{$page->title}' retrieved successfully.");
    }
}
