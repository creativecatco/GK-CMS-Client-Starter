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
        return 'Get detailed information about a specific page, including its template code, fields, CSS, and settings. ALWAYS use this before modifying a page template to understand its current structure. Pay attention to page_type — "header" and "footer" types are GLOBAL components that render on every page.';
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

        $pageType = $page->page_type ?? 'page';
        $isGlobal = in_array($pageType, ['header', 'footer']);

        $message = "Page '{$page->title}' retrieved successfully.";
        if ($isGlobal) {
            $message .= " ⚠️ This is a GLOBAL {$pageType} component — it renders on EVERY page. Use update_page_fields to change content. Only use update_page_template if you need to change the HTML structure, and be extremely careful.";
        }

        return $this->success([
            'id' => $page->id,
            'title' => $page->title,
            'slug' => $page->slug,
            'page_type' => $pageType,
            'is_global_component' => $isGlobal,
            'template' => $page->template,
            'custom_template' => $page->custom_template ?? '',
            'fields' => $page->fields ?? [],
            'field_definitions' => $page->field_definitions ?? [],
            'field_keys' => array_keys($page->fields ?? []),
            'custom_css' => $page->custom_css ?? '',
            'featured_image' => $page->featured_image,
            'status' => $page->status,
            'sort_order' => $page->sort_order,
            'seo_title' => $page->seo_title,
            'seo_description' => $page->seo_description,
        ], $message);
    }
}
