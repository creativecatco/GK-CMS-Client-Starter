<?php

namespace CreativeCatCo\GkCmsCore\Services\Ai\Tools;

use CreativeCatCo\GkCmsCore\Models\Page;

class ListPagesTool extends AbstractTool
{
    public function name(): string
    {
        return 'list_pages';
    }

    public function description(): string
    {
        return 'List all pages in the CMS with their titles, slugs, templates, types, and status. Pages with page_type "header" or "footer" are GLOBAL components that render on every page — modify these with extreme caution.';
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => new \stdClass(),
            'required' => [],
        ];
    }

    public function execute(array $params): array
    {
        $pages = Page::orderBy('sort_order')->get()->map(function ($page) {
            $pageType = $page->page_type ?? 'page';
            $isGlobal = in_array($pageType, ['header', 'footer']);

            return [
                'id' => $page->id,
                'title' => $page->title,
                'slug' => $page->slug,
                'page_type' => $pageType,
                'is_global_component' => $isGlobal,
                'template' => $page->template,
                'status' => $page->status,
                'sort_order' => $page->sort_order,
                'has_custom_template' => !empty($page->custom_template),
                'field_count' => is_array($page->fields) ? count($page->fields) : 0,
            ];
        })->toArray();

        $globalCount = count(array_filter($pages, fn($p) => $p['is_global_component']));
        $pageCount = count($pages) - $globalCount;

        return $this->success($pages, "{$pageCount} pages and {$globalCount} global components found.");
    }
}
