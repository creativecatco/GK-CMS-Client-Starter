<?php

namespace CreativeCatCo\GkCmsCore\Services\Ai\Tools;

use CreativeCatCo\GkCmsCore\Models\Page;

/**
 * Returns the FULL, untruncated template code for a page.
 *
 * Unlike get_page_info (which truncates the template to save tokens),
 * this tool returns the complete template. Use this when you need to:
 * - Make a precise patch_page_template edit (need exact text to match)
 * - Understand the full page structure before restructuring
 * - Debug template rendering issues
 */
class GetPageTemplateTool extends AbstractTool
{
    public function name(): string
    {
        return 'get_page_template';
    }

    public function description(): string
    {
        return <<<'DESC'
Get the FULL, untruncated template code for a page. Use this when you need the exact template text for:
- Making a precise `patch_page_template` edit (you need the exact string to find)
- Understanding the complete page structure
- Debugging template rendering issues

For a general page overview (fields, settings, etc.), use `get_page_info` instead — it's faster and cheaper.
DESC;
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'slug' => [
                    'type' => 'string',
                    'description' => 'The URL slug of the page (e.g., "home", "about", "site-header").',
                ],
                'section' => [
                    'type' => 'string',
                    'description' => 'Optional: return only the portion of the template containing this text. Useful for large templates when you only need one section. The tool returns 500 chars of context around the match.',
                ],
            ],
            'required' => ['slug'],
        ];
    }

    public function execute(array $params): array
    {
        $slug = $params['slug'] ?? '';
        $section = $params['section'] ?? null;

        if (empty($slug)) {
            return $this->error('The \'slug\' parameter is required.');
        }

        $page = Page::where('slug', $slug)->first();
        if (!$page) {
            return $this->error("Page not found with slug: '{$slug}'. Use list_pages to see available pages.");
        }

        $template = $page->custom_template ?? '';

        if (empty($template)) {
            // Check if the page uses a named template (not custom)
            $templateName = $page->template ?? 'custom';

            if ($templateName !== 'custom') {
                return $this->error(
                    "Page '{$slug}' uses the built-in '{$templateName}' template (not a custom template). " .
                    "There is no custom_template code to read. " .
                    "To modify this page's template, you must first create a custom template using update_page_template."
                );
            }

            return $this->error(
                "Page '{$slug}' has no custom template set. " .
                "This page may use a default/built-in template. " .
                "To add a custom template, use update_page_template."
            );
        }

        // If a section filter is provided, extract just that portion
        if (!empty($section)) {
            $pos = stripos($template, $section);
            if ($pos === false) {
                return $this->error(
                    "The text '{$section}' was not found in the template for page '{$slug}'. " .
                    "Try a different search term, or omit the 'section' parameter to get the full template."
                );
            }

            // Return 500 chars of context around the match
            $contextBefore = 500;
            $contextAfter = 500;
            $start = max(0, $pos - $contextBefore);
            $end = min(strlen($template), $pos + strlen($section) + $contextAfter);
            $excerpt = mb_substr($template, $start, $end - $start);

            $prefix = $start > 0 ? '... [template content before]\n' : '';
            $suffix = $end < strlen($template) ? '\n... [template content after]' : '';

            return $this->success([
                'slug' => $page->slug,
                'title' => $page->title,
                'template_excerpt' => $prefix . $excerpt . $suffix,
                'match_position' => $pos,
                'total_template_length' => strlen($template),
            ], "Found '{$section}' in template for page '{$page->title}'. Showing surrounding context. Use the exact text from this excerpt for patch_page_template find/replace.");
        }

        return $this->success([
            'slug' => $page->slug,
            'title' => $page->title,
            'page_type' => $page->page_type ?? 'page',
            'template_name' => $page->template ?? 'custom',
            'custom_template' => $template,
            'template_length' => strlen($template),
        ], "Full template for page '{$page->title}' ({$page->slug}). Use the exact text from this template for patch_page_template find/replace operations.");
    }
}
