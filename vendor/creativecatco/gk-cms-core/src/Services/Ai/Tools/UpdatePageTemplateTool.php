<?php

namespace CreativeCatCo\GkCmsCore\Services\Ai\Tools;

use CreativeCatCo\GkCmsCore\Models\Page;

class UpdatePageTemplateTool extends AbstractTool
{
    public function name(): string
    {
        return 'update_page_template';
    }

    public function description(): string
    {
        return 'Update the Blade template code for an existing page. This replaces the entire template. The new template must use data-field attributes for inline editing. Field definitions will be re-discovered from the new template, and existing field values will be preserved where keys match.';
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
                'template_code' => [
                    'type' => 'string',
                    'description' => 'The complete new Blade template code. Must use data-field attributes for editable content, CSS variables for theme colors/fonts, and Tailwind CSS for layout.',
                ],
            ],
            'required' => ['slug', 'template_code'],
        ];
    }

    public function execute(array $params): array
    {
        $slug = $params['slug'];
        $templateCode = $params['template_code'];

        $page = Page::where('slug', $slug)->first();
        if (!$page) {
            return $this->error("Page not found with slug: '{$slug}'. Use list_pages to see available pages.");
        }

        // Re-discover field definitions from the new template
        $newFieldDefs = Page::discoverFieldsFromTemplate($templateCode);

        // Preserve existing field values where keys still exist
        $existingFields = $page->fields ?? [];
        $newFieldKeys = array_column($newFieldDefs, 'key');
        $preservedFields = [];
        foreach ($newFieldKeys as $key) {
            if (isset($existingFields[$key])) {
                $preservedFields[$key] = $existingFields[$key];
            }
        }

        try {
            $page->update([
                'template' => 'custom',
                'custom_template' => $templateCode,
                'field_definitions' => $newFieldDefs,
                'fields' => array_merge($preservedFields, $page->fields ?? []),
            ]);

            $addedFields = array_diff($newFieldKeys, array_keys($existingFields));
            $removedFields = array_diff(array_keys($existingFields), $newFieldKeys);

            return $this->success([
                'slug' => $page->slug,
                'title' => $page->title,
                'field_count' => count($newFieldDefs),
                'fields_discovered' => $newFieldKeys,
                'fields_added' => array_values($addedFields),
                'fields_removed' => array_values($removedFields),
                'fields_preserved' => count($preservedFields),
            ], "Template for page '{$page->title}' updated successfully.");
        } catch (\Exception $e) {
            return $this->error("Failed to update template: {$e->getMessage()}");
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
            'template' => $page->template,
            'custom_template' => $page->custom_template,
            'field_definitions' => $page->field_definitions,
            'fields' => $page->fields,
        ];
    }

    public function rollback(array $rollbackData): bool
    {
        $page = Page::where('slug', $rollbackData['slug'] ?? '')->first();
        if (!$page) {
            return false;
        }

        $page->update([
            'template' => $rollbackData['template'],
            'custom_template' => $rollbackData['custom_template'],
            'field_definitions' => $rollbackData['field_definitions'],
            'fields' => $rollbackData['fields'],
        ]);

        return true;
    }
}
