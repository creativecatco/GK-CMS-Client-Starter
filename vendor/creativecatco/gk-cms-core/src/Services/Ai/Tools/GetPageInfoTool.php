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
        return 'Get a compact overview of a page: its template code, field names with types and short previews, CSS, and settings. ALWAYS call this before modifying a page. To read the full value of a specific field, use get_field_value after reviewing the overview.';
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

        // Build a compact field summary (type + short preview, NOT full values)
        $fieldSummary = $this->buildFieldSummary($page);

        $message = "Page '{$page->title}' retrieved successfully.";
        if ($isGlobal) {
            $message .= " WARNING: GLOBAL {$pageType} — renders on EVERY page. Prefer update_page_fields over update_page_template.";
        }

        // Add helpful hints based on field types present
        $hints = $this->getFieldHints($fieldSummary);
        if (!empty($hints)) {
            $message .= "\n\nField format hints:\n" . implode("\n", $hints);
        }

        $message .= "\n\nTo read the full value of any field, use get_field_value with the page slug and field key.";

        // Truncate template to a reasonable preview
        $template = $page->custom_template ?? '';
        $templatePreview = $template;
        if (strlen($template) > 4000) {
            $templatePreview = mb_substr($template, 0, 4000) . "\n... [template truncated — use patch_page_template for small fixes]";
        }

        return $this->success([
            'id' => $page->id,
            'title' => $page->title,
            'slug' => $page->slug,
            'page_type' => $pageType,
            'is_global_component' => $isGlobal,
            'template' => $page->template,
            'custom_template' => $templatePreview,
            'field_summary' => $fieldSummary,
            'custom_css' => $page->custom_css ?? '',
            'status' => $page->status,
            'seo_title' => $page->seo_title,
            'seo_description' => $page->seo_description,
        ], $message);
    }

    /**
     * Build a compact field summary: type + short preview for each field.
     * Full values are NOT included — use get_field_value for those.
     */
    protected function buildFieldSummary(Page $page): array
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

        $summary = [];
        foreach ($fields as $key => $value) {
            $def = $defByKey[$key] ?? null;
            $type = $def['type'] ?? $this->inferFieldType($key, $value);

            $entry = [
                'type' => $type,
                'preview' => $this->makePreview($type, $value),
            ];

            if ($def && isset($def['label'])) {
                $entry['label'] = $def['label'];
            }

            $summary[$key] = $entry;
        }

        // Also include defined fields that don't have values yet
        foreach ($defByKey as $key => $def) {
            if (!isset($summary[$key])) {
                $summary[$key] = [
                    'type' => $def['type'] ?? 'text',
                    'preview' => '(empty)',
                    'label' => $def['label'] ?? $key,
                ];
            }
        }

        return $summary;
    }

    /**
     * Create a short preview of a field value based on its type.
     * This keeps the response compact while giving the AI enough context.
     */
    protected function makePreview(string $type, $value): string
    {
        if ($value === null || $value === '') {
            return '(empty)';
        }

        switch ($type) {
            case 'section_bg':
                if (is_array($value)) {
                    $parts = [];
                    if (!empty($value['image'])) {
                        $parts[] = 'image: ' . basename($value['image']);
                    }
                    if (!empty($value['color'])) {
                        $parts[] = 'color: ' . $value['color'];
                    }
                    if (!empty($value['mode'])) {
                        $parts[] = 'mode: ' . $value['mode'];
                    }
                    return implode(', ', $parts) ?: '(set but empty)';
                }
                if (is_string($value)) {
                    return mb_substr($value, 0, 80);
                }
                return '(set)';

            case 'button':
                if (is_array($value) && isset($value['text'])) {
                    return '"' . $value['text'] . '" -> ' . ($value['link'] ?? '');
                }
                return '(set)';

            case 'button_group':
                if (is_array($value)) {
                    return count($value) . ' buttons';
                }
                return '(set)';

            case 'repeater':
                if (is_array($value)) {
                    return count($value) . ' items';
                }
                return '(set)';

            case 'gallery':
                if (is_array($value)) {
                    return count($value) . ' images';
                }
                return '(set)';

            case 'image':
                if (is_string($value)) {
                    return basename($value);
                }
                return '(set)';

            case 'richtext':
                if (is_string($value)) {
                    $plain = strip_tags($value);
                    return '"' . mb_substr($plain, 0, 80) . (strlen($plain) > 80 ? '...' : '') . '"';
                }
                return '(set)';

            case 'toggle':
                return $value ? 'true' : 'false';

            case 'color':
                return is_string($value) ? $value : '(set)';

            case 'url':
                return is_string($value) ? $value : '(set)';

            case 'number':
                return (string) $value;

            default: // text, textarea, etc.
                if (is_string($value)) {
                    return '"' . mb_substr($value, 0, 80) . (strlen($value) > 80 ? '...' : '') . '"';
                }
                if (is_array($value)) {
                    return json_encode($value, JSON_UNESCAPED_SLASHES);
                }
                return (string) $value;
        }
    }

    /**
     * Infer field type from key name and value when no definition exists.
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
     * Generate helpful hints based on field types present on the page.
     */
    protected function getFieldHints(array $fieldSummary): array
    {
        $hints = [];
        $typesPresent = array_unique(array_column($fieldSummary, 'type'));

        if (in_array('section_bg', $typesPresent)) {
            $hints[] = '- section_bg fields are JSON objects. Use get_field_value to read the full current value before updating. Only change the "image" key while preserving other properties.';
            $hints[] = '- Image paths in section_bg must be relative to storage/ (e.g., "media/ai-generated/hero.png"), NOT full URLs.';
        }

        if (in_array('button', $typesPresent)) {
            $hints[] = '- button fields are JSON objects with text, link, style, and visible keys. Use get_field_value to read the full current value.';
        }

        if (in_array('image', $typesPresent)) {
            $hints[] = '- image fields are simple string paths relative to storage/ (e.g., "uploads/photo.jpg").';
        }

        if (in_array('repeater', $typesPresent)) {
            $hints[] = '- repeater fields are JSON arrays of objects. Use get_field_value to read the full structure before modifying.';
        }

        return $hints;
    }
}
