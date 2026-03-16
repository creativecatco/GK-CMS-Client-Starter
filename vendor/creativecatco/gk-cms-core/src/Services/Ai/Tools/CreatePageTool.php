<?php

namespace CreativeCatCo\GkCmsCore\Services\Ai\Tools;

use CreativeCatCo\GkCmsCore\Models\Page;

class CreatePageTool extends AbstractTool
{
    public function name(): string
    {
        return 'create_page';
    }

    public function description(): string
    {
        return 'Create a new page with a custom Blade template and field values. The template_code must be valid Blade HTML that extends the CMS layout and uses data-field attributes for inline editing. Fields are auto-discovered from the template. The page will be accessible at /{slug}.';
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'title' => [
                    'type' => 'string',
                    'description' => 'The page title (e.g., "About Us", "Contact", "Services").',
                ],
                'slug' => [
                    'type' => 'string',
                    'description' => 'The URL slug for the page (e.g., "about", "contact", "services"). Must be lowercase, alphanumeric with hyphens only.',
                ],
                'template_code' => [
                    'type' => 'string',
                    'description' => 'The full Blade template code for the page. Must use data-field attributes for editable content, CSS variables for theme colors/fonts, and Tailwind CSS for layout. Do NOT include @extends or layout wrappers — only the page body content (sections).',
                ],
                'fields' => [
                    'type' => 'object',
                    'description' => 'Key-value pairs of field values to populate the page. Keys must match the data-field attributes in the template_code.',
                    'additionalProperties' => true,
                ],
                'status' => [
                    'type' => 'string',
                    'enum' => ['published', 'draft'],
                    'description' => 'The page status. Default is "published".',
                ],
                'page_type' => [
                    'type' => 'string',
                    'enum' => ['page', 'header', 'footer'],
                    'description' => 'The type of page. Use "page" for normal pages, "header" for the site header, "footer" for the site footer. Default is "page".',
                ],
                'seo_title' => [
                    'type' => 'string',
                    'description' => 'Custom SEO title for the page. Defaults to the page title if not provided.',
                ],
                'seo_description' => [
                    'type' => 'string',
                    'description' => 'SEO meta description for the page (recommended 150-160 characters).',
                ],
            ],
            'required' => ['title', 'template_code'],
        ];
    }

    public function execute(array $params): array
    {
        // Validate required parameters
        if (empty($params['title'])) {
            return $this->error('Missing required parameter: title. Received params: ' . json_encode(array_keys($params)));
        }
        if (empty($params['template_code'])) {
            return $this->error('Missing required parameter: template_code. Received params: ' . json_encode(array_keys($params)));
        }

        $title = $params['title'];
        $slug = $params['slug'] ?? \Illuminate\Support\Str::slug($title);
        $templateCode = $params['template_code'];
        $fields = $params['fields'] ?? [];
        $status = $params['status'] ?? 'published';
        $pageType = $params['page_type'] ?? 'page';

        // Check if slug already exists
        if (Page::where('slug', $slug)->exists()) {
            return $this->error("A page with slug '{$slug}' already exists. Use update_page_template to modify it, or choose a different slug.");
        }

        // Auto-discover field definitions from the template
        $fieldDefinitions = Page::discoverFieldsFromTemplate($templateCode);

        // Determine sort order
        $maxSort = Page::max('sort_order') ?? 0;

        try {
            $page = Page::create([
                'title' => $title,
                'slug' => $slug,
                'page_type' => $pageType,
                'template' => 'custom',
                'custom_template' => $templateCode,
                'fields' => $fields,
                'field_definitions' => $fieldDefinitions,
                'status' => $status,
                'sort_order' => $maxSort + 1,
                'seo_title' => $params['seo_title'] ?? null,
                'seo_description' => $params['seo_description'] ?? null,
            ]);

            return $this->success([
                'id' => $page->id,
                'title' => $page->title,
                'slug' => $page->slug,
                'url' => '/' . $page->slug,
                'status' => $page->status,
                'field_count' => count($fieldDefinitions),
                'fields_discovered' => array_column($fieldDefinitions, 'key'),
            ], "Page '{$title}' created successfully at /{$slug}.");
        } catch (\Exception $e) {
            return $this->error("Failed to create page: {$e->getMessage()}");
        }
    }

    public function captureRollbackData(array $params): array
    {
        $slug = $params['slug'] ?? \Illuminate\Support\Str::slug($params['title'] ?? '');
        return ['slug' => $slug, 'action' => 'create'];
    }

    public function rollback(array $rollbackData): bool
    {
        if (($rollbackData['action'] ?? '') !== 'create') {
            return false;
        }

        $page = Page::where('slug', $rollbackData['slug'])->first();
        if ($page) {
            $page->delete();
            return true;
        }
        return false;
    }
}
