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
        return 'Get detailed information about a specific page, including its template code, fields with their types and current values, CSS, and settings. ALWAYS use this before modifying a page to understand its current structure. The response includes field_map which shows each field\'s type and current value — use this to understand the correct data format before calling update_page_fields.';
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

        // Build a field map that includes type info alongside values
        $fieldMap = $this->buildFieldMap($page);

        $message = "Page '{$page->title}' retrieved successfully.";
        if ($isGlobal) {
            $message .= " ⚠️ GLOBAL {$pageType} — renders on EVERY page. Prefer update_page_fields over update_page_template.";
        }

        // Add helpful hints based on field types present
        $hints = $this->getFieldHints($fieldMap);
        if (!empty($hints)) {
            $message .= "\n\nField format hints:\n" . implode("\n", $hints);
        }

        return $this->success([
            'id' => $page->id,
            'title' => $page->title,
            'slug' => $page->slug,
            'page_type' => $pageType,
            'is_global_component' => $isGlobal,
            'template' => $page->template,
            'custom_template' => $page->custom_template ?? '',
            'field_map' => $fieldMap,
            'fields' => $page->fields ?? [],
            'field_definitions' => $page->field_definitions ?? [],
            'custom_css' => $page->custom_css ?? '',
            'featured_image' => $page->featured_image,
            'status' => $page->status,
            'sort_order' => $page->sort_order,
            'seo_title' => $page->seo_title,
            'seo_description' => $page->seo_description,
        ], $message);
    }

    /**
     * Build a field map that pairs each field's value with its type definition.
     * This helps the AI understand the correct data format for each field.
     */
    protected function buildFieldMap(Page $page): array
    {
        $fields = $page->fields ?? [];
        $definitions = $page->field_definitions ?? [];

        // Index definitions by key for quick lookup
        $defByKey = [];
        foreach ($definitions as $def) {
            if (isset($def['key'])) {
                $defByKey[$def['key']] = $def;
            }
        }

        $map = [];
        foreach ($fields as $key => $value) {
            $def = $defByKey[$key] ?? null;
            $type = $def['type'] ?? $this->inferFieldType($key, $value);

            $entry = [
                'type' => $type,
                'value' => $value,
            ];

            // Add label if available
            if ($def && isset($def['label'])) {
                $entry['label'] = $def['label'];
            }

            // Add format hint for complex types
            $formatHint = $this->getFormatHint($type, $value);
            if ($formatHint) {
                $entry['format_hint'] = $formatHint;
            }

            $map[$key] = $entry;
        }

        // Also include defined fields that don't have values yet
        foreach ($defByKey as $key => $def) {
            if (!isset($map[$key])) {
                $map[$key] = [
                    'type' => $def['type'] ?? 'text',
                    'value' => null,
                    'label' => $def['label'] ?? $key,
                    'format_hint' => $this->getFormatHint($def['type'] ?? 'text', null),
                ];
            }
        }

        return $map;
    }

    /**
     * Infer field type from key name and value when no definition exists.
     */
    protected function inferFieldType(string $key, $value): string
    {
        // Check key name patterns
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
     * Get a format hint for a specific field type.
     */
    protected function getFormatHint(string $type, $currentValue): ?string
    {
        return match ($type) {
            'section_bg' => 'JSON object: {"image": "path/relative/to/storage", "mode": "cover|contain|repeat|fixed", "color": "#hex or null", "colorType": "solid|gradient", "gradient": "css-gradient or null", "overlay": {"type": "none|solid|gradient", "solid": "rgba(...)", "gradient": {"type": "linear|radial", "angle": 180, "color1": "...", "color2": "..."}}}. To set just an image: keep existing values and only change the "image" key.',
            'button' => 'JSON object: {"text": "Button Text", "link": "/url", "style": "primary|secondary", "visible": true}',
            'button_group' => 'JSON array of button objects: [{"text": "...", "link": "...", "style": "primary", "visible": true}, ...]',
            'image' => 'String path relative to storage, e.g., "media/ai-generated/filename.png" or "uploads/photo.jpg"',
            'color' => 'Hex color string, e.g., "#1a1a2e"',
            'url' => 'URL string, e.g., "/contact" or "https://example.com"',
            'toggle' => 'Boolean: true or false',
            'number' => 'Numeric value',
            'repeater' => 'JSON array of objects, each with sub-fields matching the repeater definition',
            'gallery' => 'JSON array of image objects: [{"src": "path/to/image.jpg", "alt": "description"}, ...]',
            default => null,
        };
    }

    /**
     * Generate helpful hints based on field types present on the page.
     */
    protected function getFieldHints(array $fieldMap): array
    {
        $hints = [];
        $typesPresent = array_unique(array_column($fieldMap, 'type'));

        if (in_array('section_bg', $typesPresent)) {
            $hints[] = '- section_bg fields are JSON objects. To update the background image, get the current value first, then update only the "image" key while preserving other properties (color, mode, overlay).';
            $hints[] = '- Image paths in section_bg must be relative to storage/ (e.g., "media/ai-generated/hero.png"), NOT full URLs.';
        }

        if (in_array('button', $typesPresent)) {
            $hints[] = '- button fields are JSON objects with text, link, style, and visible keys.';
        }

        if (in_array('image', $typesPresent)) {
            $hints[] = '- image fields are simple string paths relative to storage/ (e.g., "uploads/photo.jpg").';
        }

        return $hints;
    }
}
