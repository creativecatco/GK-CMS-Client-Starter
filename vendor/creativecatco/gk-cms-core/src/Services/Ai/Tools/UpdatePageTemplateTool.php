<?php

namespace CreativeCatCo\GkCmsCore\Services\Ai\Tools;

use CreativeCatCo\GkCmsCore\Models\Page;
use Illuminate\Support\Facades\Log;

class UpdatePageTemplateTool extends AbstractTool
{
    /**
     * Global component slugs that require extra caution.
     * These render on EVERY page — breaking them breaks the entire site.
     */
    protected const GLOBAL_COMPONENT_SLUGS = [
        'site-header', '_header', 'header',
        'site-footer', '_footer', 'footer',
    ];

    public function name(): string
    {
        return 'update_page_template';
    }

    public function description(): string
    {
        return <<<'DESC'
Update the Blade template code for an existing page. This REPLACES the entire template — use with caution.

⚠️ CRITICAL RULES:
1. NEVER modify header/footer templates unless the user EXPLICITLY asks you to change the header/footer template structure.
2. To change a hero IMAGE on a page, use `update_page_fields` — do NOT rewrite the template.
3. To change text/content, use `update_page_fields` — do NOT rewrite the template.
4. Only use this tool when you need to change the LAYOUT/STRUCTURE of a page (add sections, rearrange elements, change the HTML structure).
5. Always use `get_page_info` first to see the current template before modifying it.
6. The new template must preserve ALL existing data-field attributes and field keys to avoid breaking inline editing.

The new template must use data-field attributes for inline editing. Field definitions will be re-discovered from the new template, and existing field values will be preserved where keys match.
DESC;
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
        $slug = $params['slug'] ?? null;
        $templateCode = $params['template_code'] ?? null;

        if (empty($slug)) {
            return $this->error('The \'slug\' parameter is required to update a page template.');
        }

        if (empty($templateCode)) {
            return $this->error('The \'template_code\' parameter is required to update a page template.');
        }

        $page = Page::where('slug', $slug)->first();
        if (!$page) {
            return $this->error("Page not found with slug: '{$slug}'. Use list_pages to see available pages.");
        }

        // ── Safety check: Global component protection ──
        $isGlobal = $this->isGlobalComponent($page);
        if ($isGlobal) {
            Log::warning('AI is modifying a global component template', [
                'slug' => $slug,
                'title' => $page->title,
                'page_type' => $page->page_type ?? 'unknown',
            ]);

            // Block if the new template contains Blade code that references
            // variables not available in the component rendering context
            $dangerousVars = [];
            if (preg_match_all('/\{\{.*?\$(\w+)/', $templateCode, $varMatches)) {
                $safeVars = ['fields', 'page', 'loop', 'item', 'key', 'value', 'slot'];
                foreach ($varMatches[1] as $var) {
                    if (!in_array($var, $safeVars)) {
                        $dangerousVars[] = '$' . $var;
                    }
                }
            }

            if (!empty($dangerousVars)) {
                return $this->error(
                    "BLOCKED: Template for global component '{$slug}' references undefined variables: " .
                    implode(', ', array_unique($dangerousVars)) . ".\n\n" .
                    "Headers and footers only have access to \$fields and \$page. " .
                    "Using other variables will crash the ENTIRE site.\n\n" .
                    "Safe alternatives:\n" .
                    "- Navigation links: use update_menu tool\n" .
                    "- Text/content: use update_page_fields tool\n" .
                    "- Colors/styles: use update_css tool"
                );
            }
        }

        // ── Validation: Check template for common issues ──
        $validationErrors = $this->validateTemplate($templateCode, $page);
        if (!empty($validationErrors)) {
            return $this->error(
                "Template validation failed. Fix these issues before saving:\n- " . implode("\n- ", $validationErrors)
            );
        }

        // ── Validation: Check for field key preservation ──
        $existingFields = $page->fields ?? [];
        $existingFieldKeys = array_keys($existingFields);
        $newFieldDefs = Page::discoverFieldsFromTemplate($templateCode);
        $newFieldKeys = array_column($newFieldDefs, 'key');

        $droppedFields = array_diff($existingFieldKeys, $newFieldKeys);
        $addedFields = array_diff($newFieldKeys, $existingFieldKeys);

        // Warn if dropping many fields (sign of a bad template replacement)
        if (count($droppedFields) > 3 && count($droppedFields) > count($existingFieldKeys) * 0.5) {
            return $this->error(
                "This template would remove " . count($droppedFields) . " out of " . count($existingFieldKeys) . " existing fields: " .
                implode(', ', array_slice($droppedFields, 0, 10)) .
                ". This is likely a mistake — you may be replacing the template with something too different. " .
                "Use get_page_info to review the current template first, and preserve existing field keys."
            );
        }

        // Preserve existing field values where keys still exist
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

            $resultMessage = "Template for page '{$page->title}' updated successfully.";

            if ($isGlobal) {
                $resultMessage .= " ⚠️ This is a GLOBAL component — it affects EVERY page on the site. Verify with render_page immediately.";
            }

            if (!empty($droppedFields)) {
                $resultMessage .= " Note: " . count($droppedFields) . " field(s) were removed: " . implode(', ', $droppedFields) . ".";
            }

            if (!empty($addedFields)) {
                $resultMessage .= " Added " . count($addedFields) . " new field(s): " . implode(', ', array_values($addedFields)) . ".";
            }

            return $this->success([
                'slug' => $page->slug,
                'title' => $page->title,
                'is_global_component' => $isGlobal,
                'field_count' => count($newFieldDefs),
                'fields_discovered' => $newFieldKeys,
                'fields_added' => array_values($addedFields),
                'fields_removed' => array_values($droppedFields),
                'fields_preserved' => count($preservedFields),
            ], $resultMessage);
        } catch (\Exception $e) {
            return $this->error("Failed to update template: {$e->getMessage()}");
        }
    }

    /**
     * Validate a template for common issues before saving.
     */
    protected function validateTemplate(string $template, Page $page): array
    {
        $errors = [];

        // Check for empty or trivially small templates
        if (strlen(trim($template)) < 50) {
            $errors[] = "Template is too short (" . strlen(trim($template)) . " chars). A valid template should have meaningful HTML content.";
        }

        // Check for unmatched Blade directives
        $openSection = substr_count($template, '@section(');
        $closeSection = substr_count($template, '@endsection');
        if ($openSection > 0 && $openSection !== $closeSection) {
            $errors[] = "Unmatched @section/@endsection directives ({$openSection} opens, {$closeSection} closes).";
        }

        $openForeach = substr_count($template, '@foreach');
        $closeForeach = substr_count($template, '@endforeach');
        if ($openForeach !== $closeForeach) {
            $errors[] = "Unmatched @foreach/@endforeach ({$openForeach} opens, {$closeForeach} closes).";
        }

        $openIf = substr_count($template, '@if(') + substr_count($template, '@if (');
        $closeIf = substr_count($template, '@endif');
        if ($openIf !== $closeIf) {
            $errors[] = "Unmatched @if/@endif ({$openIf} opens, {$closeIf} closes).";
        }

        $openPush = substr_count($template, '@push(');
        $closePush = substr_count($template, '@endpush');
        if ($openPush !== $closePush) {
            $errors[] = "Unmatched @push/@endpush ({$openPush} opens, {$closePush} closes).";
        }

        // Check for at least one data-field attribute (required for inline editing)
        if (!str_contains($template, 'data-field=')) {
            $errors[] = "Template has no data-field attributes. Every editable element must have data-field and data-field-type attributes for inline editing to work.";
        }

        // Check for hardcoded colors (should use CSS variables)
        if (preg_match('/(?:background-color|color|border-color)\s*:\s*#[0-9a-fA-F]{3,8}/', $template)) {
            // This is a warning, not a hard error
            Log::warning('Template contains hardcoded colors', ['slug' => $page->slug]);
        }

        // Check for PHP syntax issues in Blade expressions
        if (preg_match('/\{\{\s*\$(?!fields|page|loop|index|item|key|value|slot|errors|message)/', $template, $matches)) {
            // Allow common Blade variables but flag unusual ones
            Log::info('Template uses non-standard variable', ['match' => $matches[0], 'slug' => $page->slug]);
        }

        return $errors;
    }

    /**
     * Check if a page is a global component (header/footer).
     */
    protected function isGlobalComponent(Page $page): bool
    {
        // Check by slug
        if (in_array($page->slug, self::GLOBAL_COMPONENT_SLUGS)) {
            return true;
        }

        // Check by page_type
        $pageType = $page->page_type ?? '';
        if (in_array($pageType, ['header', 'footer'])) {
            return true;
        }

        // Check by title pattern
        $titleLower = strtolower($page->title ?? '');
        if (str_contains($titleLower, 'header') || str_contains($titleLower, 'footer')) {
            return true;
        }

        return false;
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

        try {
            $page->update([
                'template' => $rollbackData['template'],
                'custom_template' => $rollbackData['custom_template'],
                'field_definitions' => $rollbackData['field_definitions'],
                'fields' => $rollbackData['fields'],
            ]);
            return true;
        } catch (\Exception $e) {
            Log::error('Template rollback failed', [
                'slug' => $rollbackData['slug'],
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }
}
