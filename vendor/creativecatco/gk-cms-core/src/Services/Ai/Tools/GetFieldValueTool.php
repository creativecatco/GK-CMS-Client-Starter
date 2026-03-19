<?php

namespace CreativeCatCo\GkCmsCore\Services\Ai\Tools;

use CreativeCatCo\GkCmsCore\Models\Page;

class GetFieldValueTool extends AbstractTool
{
    public function name(): string
    {
        return 'get_field_value';
    }

    public function description(): string
    {
        return 'Get the full current value of one or more specific fields on a page. Use this after get_page_info to read the complete data for fields you need to work with. Especially important for complex types like section_bg, button, repeater, and richtext where you need the full value before making updates.';
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'slug' => [
                    'type' => 'string',
                    'description' => 'The URL slug of the page (e.g., "home", "about").',
                ],
                'fields' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'string',
                    ],
                    'description' => 'Array of field keys to retrieve (e.g., ["hero_bg", "hero_heading"]). Max 10 fields per call.',
                ],
            ],
            'required' => ['slug', 'fields'],
        ];
    }

    public function execute(array $params): array
    {
        $slug = $params['slug'] ?? '';
        $requestedFields = $params['fields'] ?? [];

        if (empty($requestedFields)) {
            return $this->error('No field keys specified. Provide at least one field key in the "fields" array.');
        }

        if (count($requestedFields) > 10) {
            return $this->error('Too many fields requested. Max 10 per call. For a full overview, use get_page_info.');
        }

        $page = Page::where('slug', $slug)->first();
        if (!$page) {
            return $this->error("Page not found with slug: '{$slug}'. Use list_pages to see available pages.");
        }

        $allFields = $page->fields ?? [];
        $definitions = $page->field_definitions ?? [];

        // Index definitions by key
        $defByKey = [];
        foreach ($definitions as $def) {
            if (isset($def['key'])) {
                $defByKey[$def['key']] = $def;
            }
        }

        $result = [];
        $notFound = [];

        foreach ($requestedFields as $key) {
            $key = trim($key);
            if (array_key_exists($key, $allFields)) {
                $def = $defByKey[$key] ?? null;
                $type = $def['type'] ?? $this->inferFieldType($key, $allFields[$key]);

                $entry = [
                    'type' => $type,
                    'value' => $allFields[$key],
                ];

                // Add format hint for complex types
                $formatHint = $this->getFormatHint($type);
                if ($formatHint) {
                    $entry['format_hint'] = $formatHint;
                }

                $result[$key] = $entry;
            } else {
                $notFound[] = $key;
            }
        }

        $message = 'Retrieved ' . count($result) . ' field(s) from page "' . $page->title . '".';
        if (!empty($notFound)) {
            $message .= ' Not found: ' . implode(', ', $notFound) . '.';
        }

        return $this->success([
            'page_slug' => $slug,
            'fields' => $result,
        ], $message);
    }

    /**
     * Infer field type from key name and value.
     */
    protected function inferFieldType(string $key, $value): string
    {
        if (str_ends_with($key, '_bg') || str_contains($key, 'background')) {
            if (is_array($value) && (isset($value['image']) || isset($value['color']) || isset($value['mode']))) {
                return 'section_bg';
            }
        }
        if (str_ends_with($key, '_image') || str_ends_with($key, '_photo') || str_ends_with($key, '_logo')) {
            return 'image';
        }
        if (str_ends_with($key, '_url') || str_ends_with($key, '_link')) {
            return 'url';
        }
        if (str_ends_with($key, '_color')) {
            return 'color';
        }
        if (str_ends_with($key, '_button') || str_ends_with($key, '_cta')) {
            if (is_array($value) && isset($value['text'])) {
                return 'button';
            }
        }
        if (str_ends_with($key, '_buttons')) {
            return 'button_group';
        }
        if (is_array($value) && isset($value['image']) && isset($value['mode'])) {
            return 'section_bg';
        }
        if (is_array($value) && isset($value['text']) && isset($value['link'])) {
            return 'button';
        }
        if (is_string($value) && strlen($value) > 200) {
            return 'richtext';
        }

        return 'text';
    }

    /**
     * Get format hint for a field type.
     */
    protected function getFormatHint(string $type): ?string
    {
        return match ($type) {
            'section_bg' => 'JSON object with keys: image, mode, color, colorType, gradient, overlay. To update the background image, change only the "image" key and preserve all other properties. Image path must be relative to storage/ (e.g., "media/ai-generated/hero.png").',
            'button' => 'JSON object: {"text": "...", "link": "/url", "style": "primary|secondary", "visible": true}',
            'button_group' => 'JSON array of button objects',
            'repeater' => 'JSON array of objects. Preserve the existing structure when updating.',
            'gallery' => 'JSON array of image objects: [{"src": "path", "alt": "description"}, ...]',
            'image' => 'String path relative to storage/ (e.g., "media/ai-generated/filename.png")',
            default => null,
        };
    }
}
