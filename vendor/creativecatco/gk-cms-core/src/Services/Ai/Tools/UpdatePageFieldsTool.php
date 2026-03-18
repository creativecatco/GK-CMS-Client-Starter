<?php

namespace CreativeCatCo\GkCmsCore\Services\Ai\Tools;

use CreativeCatCo\GkCmsCore\Models\Page;
use Illuminate\Support\Facades\Log;

class UpdatePageFieldsTool extends AbstractTool
{
    public function name(): string
    {
        return 'update_page_fields';
    }

    public function description(): string
    {
        return 'Update field values for an existing page. This merges the provided fields with existing ones — only the specified fields are changed, others are preserved. Use this to update text, images, buttons, backgrounds, and other content without changing the template structure. IMPORTANT: For section_bg fields, the value must be a JSON object with keys like "image", "mode", "color", "overlay" — NOT a simple string. Always call get_page_info first to see the current field values and types.';
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
                    'description' => 'Key-value pairs of field values to update. Keys must match data-field attributes in the template. For section_bg fields, provide a JSON object like {"image": "media/ai-generated/hero.png", "mode": "cover", "color": null, "overlay": {"type": "none"}}. For button fields, provide {"text": "...", "link": "...", "style": "primary"}. For image fields, provide a string path relative to storage/.',
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
        $definitions = $page->field_definitions ?? [];

        // Index definitions by key
        $defByKey = [];
        foreach ($definitions as $def) {
            if (isset($def['key'])) {
                $defByKey[$def['key']] = $def;
            }
        }

        // Auto-format and validate fields before saving
        $formattedFields = [];
        $warnings = [];

        foreach ($newFields as $key => $value) {
            $fieldType = $this->resolveFieldType($key, $value, $defByKey, $existingFields);

            // Auto-format based on field type
            $formatted = $this->autoFormat($key, $value, $fieldType, $existingFields[$key] ?? null);

            if ($formatted['warning']) {
                $warnings[] = $formatted['warning'];
            }

            $formattedFields[$key] = $formatted['value'];
        }

        $mergedFields = array_merge($existingFields, $formattedFields);

        try {
            $page->update(['fields' => $mergedFields]);

            $message = "Updated " . count($newFields) . " field(s) on page '{$page->title}'.";
            if (!empty($warnings)) {
                $message .= "\n⚠️ Auto-corrections applied:\n" . implode("\n", array_map(fn($w) => "- {$w}", $warnings));
            }

            return $this->success([
                'slug' => $page->slug,
                'title' => $page->title,
                'fields_updated' => array_keys($newFields),
                'total_fields' => count($mergedFields),
                'warnings' => $warnings,
            ], $message);
        } catch (\Exception $e) {
            return $this->error("Failed to update fields: {$e->getMessage()}");
        }
    }

    /**
     * Resolve the field type from definitions, existing data, or key name inference.
     */
    protected function resolveFieldType(string $key, $value, array $defByKey, array $existingFields): string
    {
        // Check field definitions first
        if (isset($defByKey[$key]['type'])) {
            return $defByKey[$key]['type'];
        }

        // Check existing field value to infer type
        $existing = $existingFields[$key] ?? null;
        if (is_array($existing)) {
            if (isset($existing['image']) || isset($existing['mode']) || isset($existing['color'])) {
                return 'section_bg';
            }
            if (isset($existing['text']) && isset($existing['link'])) {
                return 'button';
            }
        }

        // Infer from key name
        if (str_ends_with($key, '_bg') || str_contains($key, 'background')) {
            return 'section_bg';
        }
        if (str_ends_with($key, '_button') || str_ends_with($key, '_cta')) {
            return 'button';
        }
        if (str_ends_with($key, '_image') || str_ends_with($key, '_photo') || str_ends_with($key, '_logo')) {
            return 'image';
        }

        return 'text';
    }

    /**
     * Auto-format a field value based on its type.
     * Handles common mistakes like passing a string for section_bg.
     */
    protected function autoFormat(string $key, $value, string $fieldType, $existingValue): array
    {
        $warning = null;

        switch ($fieldType) {
            case 'section_bg':
                if (is_string($value)) {
                    // AI passed a simple string — auto-wrap into section_bg object
                    $warning = "Field '{$key}' is a section_bg type but received a string. Auto-wrapped into section_bg object with the string as the image path.";
                    Log::info("UpdatePageFields: auto-formatting section_bg for '{$key}'", ['original' => $value]);

                    // Preserve existing section_bg properties, just update the image
                    $base = is_array($existingValue) ? $existingValue : [
                        'color' => null,
                        'colorType' => 'solid',
                        'gradient' => null,
                        'mode' => 'cover',
                        'overlay' => ['type' => 'none'],
                    ];
                    $base['image'] = $value;
                    $value = $base;
                } elseif (is_array($value)) {
                    // Merge with existing to preserve unset properties
                    if (is_array($existingValue)) {
                        $value = array_merge($existingValue, $value);
                    }
                }
                break;

            case 'button':
                if (is_string($value)) {
                    // AI passed just the button text
                    $warning = "Field '{$key}' is a button type but received a string. Auto-wrapped with default link and style.";
                    $base = is_array($existingValue) ? $existingValue : [
                        'link' => '#',
                        'style' => 'primary',
                        'visible' => true,
                    ];
                    $base['text'] = $value;
                    $value = $base;
                }
                break;

            case 'image':
                if (is_string($value)) {
                    // Strip leading /storage/ if the AI included it
                    $value = preg_replace('#^/?storage/#', '', $value);
                }
                break;
        }

        return ['value' => $value, 'warning' => $warning];
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
