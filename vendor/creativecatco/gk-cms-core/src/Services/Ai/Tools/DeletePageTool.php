<?php

namespace CreativeCatCo\GkCmsCore\Services\Ai\Tools;

use CreativeCatCo\GkCmsCore\Models\Page;

class DeletePageTool extends AbstractTool
{
    public function name(): string
    {
        return 'delete_page';
    }

    public function description(): string
    {
        return 'Delete a page from the CMS. This permanently removes the page, its template, fields, and CSS. Use with caution — this action can be undone via the rollback system.';
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'slug' => [
                    'type' => 'string',
                    'description' => 'The URL slug of the page to delete.',
                ],
            ],
            'required' => ['slug'],
        ];
    }

    public function execute(array $params): array
    {
        $slug = $params['slug'];

        $page = Page::where('slug', $slug)->first();
        if (!$page) {
            return $this->error("Page not found with slug: '{$slug}'.");
        }

        // Prevent deleting header/footer
        if (in_array($page->page_type, ['header', 'footer'])) {
            return $this->error("Cannot delete the site {$page->page_type}. Use update_page_template to modify it instead.");
        }

        $title = $page->title;

        try {
            $page->delete();

            return $this->success([
                'slug' => $slug,
                'title' => $title,
            ], "Page '{$title}' deleted successfully.");
        } catch (\Exception $e) {
            return $this->error("Failed to delete page: {$e->getMessage()}");
        }
    }

    public function captureRollbackData(array $params): array
    {
        $page = Page::where('slug', $params['slug'] ?? '')->first();
        if (!$page) {
            return [];
        }

        return [
            'page_data' => $page->toArray(),
        ];
    }

    public function rollback(array $rollbackData): bool
    {
        $pageData = $rollbackData['page_data'] ?? null;
        if (!$pageData) {
            return false;
        }

        // Remove id and timestamps so we create a fresh record
        unset($pageData['id'], $pageData['created_at'], $pageData['updated_at']);

        try {
            Page::create($pageData);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
