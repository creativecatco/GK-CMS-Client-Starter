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
        return 'List all pages in the CMS with their titles, slugs, templates, and status.';
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
            return [
                'id' => $page->id,
                'title' => $page->title,
                'slug' => $page->slug,
                'template' => $page->template,
                'status' => $page->status,
                'sort_order' => $page->sort_order,
                'has_custom_template' => !empty($page->custom_template),
                'field_count' => is_array($page->fields) ? count($page->fields) : 0,
            ];
        })->toArray();

        return $this->success($pages, count($pages) . ' pages found.');
    }
}
