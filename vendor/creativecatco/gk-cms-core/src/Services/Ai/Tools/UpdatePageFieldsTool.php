<?php

namespace CreativeCatCo\GkCmsCore\Services\Ai\Tools;

use CreativeCatCo\GkCmsCore\Models\Page;

class UpdatePageFieldsTool extends AbstractTool
{
    public function name(): string
    {
        return 'update_page_fields';
    }

    public function description(): string
    {
        return 'Update field values for an existing page. This merges the provided fields with existing ones — only the specified fields are changed, others are preserved. Use this to update text, images, buttons, and other content without changing the template.';
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'slug' => [
                    'type' => 'string',
                    'description' => 'The URL slug of the page to update.',
                ],
                'fields' => [
                    'type' => 'object',
                    'description' => 'Key-value pairs of field values to update. Keys must match data-field attributes in the template. Only specified fields are changed; others are preserved.',
                    'additionalProperties' => true,
                ],
            ],
            'required' => ['slug', 'fields'],
        ];
    }

    public function execute(array $params): array
    {
        $slug = $params['slug'];
        $newFields = $params['fields'] ?? [];

        if (empty($newFields)) {
            return $this->error('No fields provided to update.');
        }

        $page = Page::where('slug', $slug)->first();
        if (!$page) {
            return $this->error("Page not found with slug: '{$slug}'. Use list_pages to see available pages.");
        }

        $existingFields = $page->fields ?? [];
        $mergedFields = array_merge($existingFields, $newFields);

        try {
            $page->update(['fields' => $mergedFields]);

            return $this->success([
                'slug' => $page->slug,
                'title' => $page->title,
                'fields_updated' => array_keys($newFields),
                'total_fields' => count($mergedFields),
            ], "Updated " . count($newFields) . " field(s) on page '{$page->title}'.");
        } catch (\Exception $e) {
            return $this->error("Failed to update fields: {$e->getMessage()}");
        }
    }

    public function captureRollbackData(array $params): array
    {
        $page = Page::where('slug', $params['slug'] ?? '')->first();
        if (!$page) {
            return [];
        }

        return [
            'slug' => $page->slug,
            'fields' => $page->fields,
        ];
    }

    public function rollback(array $rollbackData): bool
    {
        $page = Page::where('slug', $rollbackData['slug'] ?? '')->first();
        if (!$page) {
            return false;
        }

        $page->update(['fields' => $rollbackData['fields']]);
        return true;
    }
}
