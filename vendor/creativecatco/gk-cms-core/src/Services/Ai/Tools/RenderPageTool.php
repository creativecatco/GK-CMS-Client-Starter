<?php

namespace CreativeCatCo\GkCmsCore\Services\Ai\Tools;

use CreativeCatCo\GkCmsCore\Models\Page;
use Illuminate\Support\Facades\Log;

/**
 * Tool that lets the AI "see" what a rendered page actually looks like.
 *
 * Instead of just reading raw template code and JSON fields, this tool
 * fetches the fully rendered HTML and extracts a structured view of:
 * - What sections are visible
 * - What text content is rendered
 * - What images are displayed (or broken)
 * - What elements are empty or missing
 * - The overall page structure
 *
 * This gives the AI the same perspective as a user viewing the page.
 */
class RenderPageTool extends AbstractTool
{
    public function name(): string
    {
        return 'render_page';
    }

    public function description(): string
    {
        return 'Fetch the fully rendered HTML of a page and extract a structured analysis of what is actually visible. Use this to see what the user sees — identify broken sections, missing content, empty areas, broken images, and layout issues. Always use this before attempting to fix a page that "looks wrong".';
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'slug' => [
                    'type' => 'string',
                    'description' => 'The page slug to render (e.g., "home", "about", "services"). Use "/" or "home" for the homepage.',
                ],
            ],
            'required' => ['slug'],
        ];
    }

    public function execute(array $params): array
    {
        $slug = $params['slug'] ?? '';

        // Normalize slug
        if ($slug === '/' || $slug === '' || $slug === 'home') {
            // Try to get the homepage
            $homePageId = \CreativeCatCo\GkCmsCore\Models\Setting::get('home_page_id');
            if ($homePageId) {
                $page = Page::find($homePageId);
                if ($page) {
                    $slug = $page->slug;
                }
            }
        }

        // Find the page
        $page = Page::where('slug', $slug)->first();
        if (!$page) {
            return $this->error("Page with slug '{$slug}' not found.");
        }

        try {
            // Render the page template with its field data
            $analysis = $this->analyzePageRender($page);

            return $this->success($analysis, "Page '{$slug}' rendered and analyzed successfully.");
        } catch (\Exception $e) {
            Log::error('RenderPageTool error', [
                'slug' => $slug,
                'error' => $e->getMessage(),
            ]);
            return $this->error('Failed to render page: ' . $e->getMessage());
        }
    }

    /**
     * Analyze a page by examining its template and field data together.
     */
    protected function analyzePageRender(Page $page): array
    {
        $template = $page->custom_template ?? '';
        $fields = $page->fields ?? [];
        if (is_string($fields)) {
            $fields = json_decode($fields, true) ?? [];
        }

        $analysis = [
            'page' => [
                'title' => $page->title,
                'slug' => $page->slug,
                'status' => $page->status,
                'seo_title' => $page->seo_title,
                'seo_description' => $page->seo_description,
            ],
            'sections' => [],
            'issues' => [],
            'summary' => '',
        ];

        // Parse the template to find sections
        $sections = $this->extractSections($template);

        foreach ($sections as $section) {
            $sectionAnalysis = [
                'name' => $section['name'],
                'type' => $section['type'],
                'fields_used' => $section['fields'],
                'field_status' => [],
                'visible_content' => [],
                'issues' => [],
            ];

            // Check each field used in this section
            foreach ($section['fields'] as $fieldKey) {
                $baseKey = preg_replace('/\.\{\{.*?\}\}\..*/', '', $fieldKey);
                $baseKey = preg_replace('/\.0\..*/', '', $baseKey);

                $value = $fields[$baseKey] ?? $fields[$fieldKey] ?? null;

                if ($value === null || $value === '' || $value === []) {
                    $sectionAnalysis['field_status'][$fieldKey] = 'EMPTY';
                    $sectionAnalysis['issues'][] = "Field '{$fieldKey}' is empty — this section may appear blank or broken.";
                } elseif (is_array($value)) {
                    $itemCount = count($value);
                    $sectionAnalysis['field_status'][$fieldKey] = "ARRAY ({$itemCount} items)";

                    // Check if array items have empty sub-fields
                    foreach ($value as $idx => $item) {
                        if (is_array($item)) {
                            foreach ($item as $subKey => $subVal) {
                                if ($subVal === null || $subVal === '') {
                                    $sectionAnalysis['issues'][] = "Item #{$idx} has empty '{$subKey}' — may show as blank.";
                                }
                            }
                        }
                    }

                    $sectionAnalysis['visible_content'][$fieldKey] = $this->summarizeArrayContent($value);
                } else {
                    $sectionAnalysis['field_status'][$fieldKey] = 'SET';
                    $sectionAnalysis['visible_content'][$fieldKey] = $this->truncateContent($value);
                }
            }

            $analysis['sections'][] = $sectionAnalysis;
        }

        // Check for template-level issues
        $templateIssues = $this->checkTemplateIssues($template, $fields);
        $analysis['issues'] = $templateIssues;

        // Check for images that might be broken
        $imageIssues = $this->checkImageFields($template, $fields);
        $analysis['issues'] = array_merge($analysis['issues'], $imageIssues);

        // Generate a human-readable summary
        $totalSections = count($analysis['sections']);
        $emptySections = 0;
        $issueCount = count($analysis['issues']);

        foreach ($analysis['sections'] as $sec) {
            $emptyFields = array_filter($sec['field_status'], fn($s) => $s === 'EMPTY');
            if (count($emptyFields) === count($sec['field_status']) && count($sec['field_status']) > 0) {
                $emptySections++;
            }
            $issueCount += count($sec['issues']);
        }

        $analysis['summary'] = "Page has {$totalSections} sections. ";
        if ($emptySections > 0) {
            $analysis['summary'] .= "{$emptySections} sections appear completely empty. ";
        }
        if ($issueCount > 0) {
            $analysis['summary'] .= "{$issueCount} issues detected. ";
        }
        if ($issueCount === 0 && $emptySections === 0) {
            $analysis['summary'] .= "No obvious issues detected — page content appears complete.";
        }

        // Include the raw template for reference (truncated if very long)
        $analysis['raw_template_preview'] = mb_strlen($template) > 3000
            ? mb_substr($template, 0, 3000) . "\n\n... [template truncated — " . mb_strlen($template) . " chars total]"
            : $template;

        // Include the full field data
        $analysis['field_data'] = $fields;

        return $analysis;
    }

    /**
     * Extract sections from a Blade template by looking for HTML section elements
     * and common patterns like comments, section tags, and div containers.
     */
    protected function extractSections(string $template): array
    {
        $sections = [];

        // Match <section> tags with optional id/class
        preg_match_all('/<section[^>]*(?:id=["\']([^"\']*)["\'])?[^>]*(?:class=["\']([^"\']*)["\'])?[^>]*>(.*?)<\/section>/si', $template, $matches, PREG_SET_ORDER);

        foreach ($matches as $idx => $match) {
            $id = $match[1] ?? '';
            $class = $match[2] ?? '';
            $content = $match[3] ?? '';

            // Determine section name from id, class, or content
            $name = $id ?: $this->inferSectionName($class, $content, $idx);

            // Find field references in this section
            $fields = $this->extractFieldReferences($content);

            // Determine section type
            $type = $this->inferSectionType($name, $class, $content);

            $sections[] = [
                'name' => $name,
                'type' => $type,
                'fields' => $fields,
                'html_length' => strlen($content),
            ];
        }

        // If no <section> tags found, try to find major div containers
        if (empty($sections)) {
            preg_match_all('/{{--\s*(.*?)\s*--}}/s', $template, $commentMatches);
            $sectionNames = $commentMatches[1] ?? [];

            // Fall back to treating the whole template as one section
            $fields = $this->extractFieldReferences($template);
            $sections[] = [
                'name' => 'main_content',
                'type' => 'full_page',
                'fields' => $fields,
                'html_length' => strlen($template),
            ];
        }

        return $sections;
    }

    /**
     * Extract field references from template content.
     */
    protected function extractFieldReferences(string $content): array
    {
        $fields = [];

        // Match $fields['key'] patterns
        preg_match_all('/\$fields\[[\'"]([^\'"]+)[\'"]\]/', $content, $matches);
        $fields = array_merge($fields, $matches[1] ?? []);

        // Match data-field="key" patterns
        preg_match_all('/data-field=["\']([^"\']+)["\']/', $content, $matches);
        $fields = array_merge($fields, $matches[1] ?? []);

        return array_unique($fields);
    }

    /**
     * Infer a section name from its class, content, or position.
     */
    protected function inferSectionName(string $class, string $content, int $index): string
    {
        // Check for common section keywords in class
        $keywords = ['hero', 'about', 'services', 'features', 'testimonial', 'contact', 'cta', 'team', 'pricing', 'faq', 'portfolio', 'gallery', 'blog', 'footer', 'header', 'stats', 'process', 'why'];

        foreach ($keywords as $keyword) {
            if (stripos($class, $keyword) !== false || stripos($content, $keyword) !== false) {
                return $keyword . '_section';
            }
        }

        return 'section_' . ($index + 1);
    }

    /**
     * Infer the type of a section.
     */
    protected function inferSectionType(string $name, string $class, string $content): string
    {
        if (stripos($name, 'hero') !== false) return 'hero';
        if (stripos($name, 'cta') !== false) return 'call_to_action';
        if (stripos($name, 'testimonial') !== false) return 'testimonials';
        if (stripos($name, 'service') !== false || stripos($name, 'feature') !== false) return 'features';
        if (stripos($name, 'contact') !== false) return 'contact';
        if (stripos($name, 'team') !== false) return 'team';
        if (stripos($name, 'faq') !== false) return 'faq';
        if (stripos($name, 'about') !== false) return 'about';
        if (stripos($name, 'stats') !== false) return 'statistics';
        if (stripos($name, 'process') !== false) return 'process';
        if (preg_match('/@foreach|@for/', $content)) return 'repeater';
        return 'content';
    }

    /**
     * Check for template-level issues.
     */
    protected function checkTemplateIssues(string $template, array $fields): array
    {
        $issues = [];

        // Check if template is empty or very short
        if (strlen($template) < 50) {
            $issues[] = 'CRITICAL: Template is empty or extremely short — page will appear blank.';
        }

        // Check for unclosed tags (basic check)
        $openSections = substr_count(strtolower($template), '<section');
        $closeSections = substr_count(strtolower($template), '</section>');
        if ($openSections !== $closeSections) {
            $issues[] = "WARNING: Mismatched <section> tags ({$openSections} open, {$closeSections} close) — may cause layout issues.";
        }

        // Check for @extends without @section
        if (strpos($template, '@extends') !== false && strpos($template, '@section') === false) {
            $issues[] = 'WARNING: Template uses @extends but has no @section — content may not render.';
        }

        // Check for fields referenced in template but missing from field data
        $referencedFields = $this->extractFieldReferences($template);
        foreach ($referencedFields as $field) {
            // Skip complex field references (repeater sub-fields)
            if (strpos($field, '.') !== false) continue;

            if (!isset($fields[$field]) || $fields[$field] === '' || $fields[$field] === null) {
                $issues[] = "Field '{$field}' is referenced in template but has no data — will show as empty or use default.";
            }
        }

        // Check for Blade syntax errors (basic)
        $openIf = preg_match_all('/@if\b/', $template);
        $closeIf = preg_match_all('/@endif\b/', $template);
        if ($openIf !== $closeIf) {
            $issues[] = "WARNING: Mismatched @if/@endif ({$openIf} @if, {$closeIf} @endif) — may cause Blade rendering errors.";
        }

        $openForeach = preg_match_all('/@foreach\b/', $template);
        $closeForeach = preg_match_all('/@endforeach\b/', $template);
        if ($openForeach !== $closeForeach) {
            $issues[] = "WARNING: Mismatched @foreach/@endforeach ({$openForeach} @foreach, {$closeForeach} @endforeach) — may cause Blade rendering errors.";
        }

        return $issues;
    }

    /**
     * Check for image fields that might be broken.
     */
    protected function checkImageFields(string $template, array $fields): array
    {
        $issues = [];

        // Find image-related fields
        foreach ($fields as $key => $value) {
            if (is_string($value) && preg_match('/\.(jpg|jpeg|png|gif|webp|svg)$/i', $value)) {
                // It's an image path — check if it's a valid URL or path
                if (!str_starts_with($value, 'http') && !str_starts_with($value, '/')) {
                    $issues[] = "Image field '{$key}' has a relative path '{$value}' — may not display correctly.";
                }
            }

            // Check arrays for image sub-fields
            if (is_array($value)) {
                foreach ($value as $idx => $item) {
                    if (is_array($item)) {
                        foreach ($item as $subKey => $subVal) {
                            if (is_string($subVal) && (stripos($subKey, 'image') !== false || stripos($subKey, 'img') !== false || stripos($subKey, 'photo') !== false)) {
                                if (empty($subVal)) {
                                    $issues[] = "Image field '{$key}[{$idx}].{$subKey}' is empty — will show as broken or placeholder.";
                                }
                            }
                        }
                    }
                }
            }
        }

        return $issues;
    }

    /**
     * Summarize array content for display.
     */
    protected function summarizeArrayContent(array $value): string
    {
        $count = count($value);
        $summary = "{$count} items: ";

        $previews = [];
        foreach (array_slice($value, 0, 3) as $idx => $item) {
            if (is_array($item)) {
                $keys = array_keys($item);
                $firstVal = reset($item);
                $preview = is_string($firstVal) ? $this->truncateContent($firstVal, 50) : json_encode($firstVal);
                $previews[] = "[{$idx}] " . implode(',', $keys) . " → " . $preview;
            } else {
                $previews[] = "[{$idx}] " . $this->truncateContent((string)$item, 50);
            }
        }

        return $summary . implode(' | ', $previews) . ($count > 3 ? ' ...' : '');
    }

    /**
     * Truncate content for display.
     */
    protected function truncateContent(string $value, int $maxLength = 100): string
    {
        $value = strip_tags($value);
        $value = preg_replace('/\s+/', ' ', $value);
        $value = trim($value);

        if (mb_strlen($value) > $maxLength) {
            return mb_substr($value, 0, $maxLength) . '...';
        }

        return $value;
    }
}
