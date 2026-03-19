<?php

namespace CreativeCatCo\GkCmsCore\Services\Ai\Tools;

use CreativeCatCo\GkCmsCore\Models\Page;
use Illuminate\Support\Facades\Log;

/**
 * Surgical find-and-replace on a page template.
 *
 * Unlike update_page_template which replaces the ENTIRE template,
 * this tool makes targeted edits — finding a specific string in the
 * template and replacing it with a new string. This is much safer
 * for small fixes like removing a hardcoded style attribute.
 *
 * Features robust fuzzy matching:
 * - Normalizes whitespace (newlines, tabs, multiple spaces → single space)
 * - Handles HTML entity variants (&amp;, &#39;, etc.)
 * - Handles escaped/unescaped quote variants
 */
class PatchPageTemplateTool extends AbstractTool
{
    public function name(): string
    {
        return 'patch_page_template';
    }

    public function description(): string
    {
        return <<<'DESC'
Make a surgical find-and-replace edit to a page's template code. Unlike `update_page_template` which replaces the ENTIRE template, this tool finds a specific string in the template and replaces it — much safer for small fixes.

IMPORTANT: Always call `get_page_template` first to see the EXACT template content before using this tool.

Use this when you need to:
- Remove a hardcoded style attribute from a section
- Fix a broken Blade directive
- Change a CSS class on a specific element
- Add or remove an attribute from a specific HTML tag
- Add a style="{{ $page->sectionBgStyle('field_name') }}" to a section

The tool uses fuzzy matching: it normalizes whitespace and handles quote/entity variations, so minor differences in spacing or quote styles won't cause failures. However, the find string should still be as close to the original as possible.

You can make multiple find-and-replace operations in a single call by providing arrays for `find` and `replace`.
DESC;
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'slug' => [
                    'type' => 'string',
                    'description' => 'The URL slug of the page to patch.',
                ],
                'find' => [
                    'oneOf' => [
                        ['type' => 'string'],
                        ['type' => 'array', 'items' => ['type' => 'string']],
                    ],
                    'description' => 'The string(s) to find in the template. The tool uses fuzzy matching (whitespace normalization, quote variants) so minor spacing differences are OK. Use get_page_template first to see the exact content.',
                ],
                'replace' => [
                    'oneOf' => [
                        ['type' => 'string'],
                        ['type' => 'array', 'items' => ['type' => 'string']],
                    ],
                    'description' => 'The replacement string(s). Use empty string "" to delete the found text. Must have the same number of items as `find` if using arrays.',
                ],
            ],
            'required' => ['slug', 'find', 'replace'],
        ];
    }

    public function execute(array $params): array
    {
        $slug = $params['slug'] ?? null;
        $find = $params['find'] ?? null;
        $replace = $params['replace'] ?? null;

        if (empty($slug)) {
            return $this->error('The \'slug\' parameter is required.');
        }

        if ($find === null || $replace === null) {
            return $this->error('Both \'find\' and \'replace\' parameters are required.');
        }

        // Normalize to arrays for batch processing
        $finds = is_array($find) ? $find : [$find];
        $replaces = is_array($replace) ? $replace : [$replace];

        if (count($finds) !== count($replaces)) {
            return $this->error('The \'find\' and \'replace\' arrays must have the same number of items. Got ' . count($finds) . ' find(s) and ' . count($replaces) . ' replace(s).');
        }

        if (count($finds) === 0) {
            return $this->error('At least one find/replace pair is required.');
        }

        if (count($finds) > 10) {
            return $this->error('Maximum 10 find/replace operations per call. You provided ' . count($finds) . '.');
        }

        $page = Page::where('slug', $slug)->first();
        if (!$page) {
            return $this->error("Page not found with slug: '{$slug}'. Use list_pages to see available pages.");
        }

        $template = $page->custom_template ?? '';
        if (empty($template)) {
            $templateName = $page->template ?? 'custom';
            if ($templateName !== 'custom') {
                return $this->error(
                    "Page '{$slug}' uses the built-in '{$templateName}' template (not a custom template). " .
                    "To modify it, first use update_page_template to create a custom template, then patch it."
                );
            }
            return $this->error(
                "Page '{$slug}' has no custom template to patch. " .
                "Use update_page_template to create a custom template first."
            );
        }

        // Perform each find-and-replace
        $results = [];
        $patchedTemplate = $template;
        $totalReplacements = 0;

        foreach ($finds as $idx => $findStr) {
            $replaceStr = $replaces[$idx];

            if (empty($findStr)) {
                $results[] = [
                    'index' => $idx,
                    'status' => 'skipped',
                    'reason' => 'Empty find string',
                ];
                continue;
            }

            // Step 1: Try exact match first
            $occurrences = substr_count($patchedTemplate, $findStr);

            if ($occurrences > 0) {
                // Exact match found — do the replacement
                if ($occurrences > 3) {
                    $results[] = [
                        'index' => $idx,
                        'status' => 'warning',
                        'reason' => "Found {$occurrences} occurrences — replacing ALL of them.",
                        'occurrences' => $occurrences,
                    ];
                }

                $patchedTemplate = str_replace($findStr, $replaceStr, $patchedTemplate);
                $totalReplacements += $occurrences;

                $results[] = [
                    'index' => $idx,
                    'status' => 'replaced',
                    'match_type' => 'exact',
                    'occurrences' => $occurrences,
                    'find_preview' => mb_substr($findStr, 0, 80),
                    'replace_preview' => mb_substr($replaceStr, 0, 80),
                ];
                continue;
            }

            // Step 2: Try fuzzy match — normalize whitespace
            $fuzzyResult = $this->fuzzyReplace($patchedTemplate, $findStr, $replaceStr);

            if ($fuzzyResult !== null) {
                $patchedTemplate = $fuzzyResult['template'];
                $totalReplacements += $fuzzyResult['count'];

                $results[] = [
                    'index' => $idx,
                    'status' => 'replaced',
                    'match_type' => $fuzzyResult['match_type'],
                    'occurrences' => $fuzzyResult['count'],
                    'find_preview' => mb_substr($findStr, 0, 80),
                    'replace_preview' => mb_substr($replaceStr, 0, 80),
                    'note' => $fuzzyResult['note'] ?? null,
                ];
                continue;
            }

            // Step 3: Try to find a partial match to give a helpful error
            $hint = $this->findPartialMatch($patchedTemplate, $findStr);

            $results[] = [
                'index' => $idx,
                'status' => 'not_found',
                'reason' => 'String not found in template even after fuzzy matching (whitespace normalization, quote variants). ' . ($hint ?: 'Use get_page_template to see the exact template content.'),
                'find_preview' => mb_substr($findStr, 0, 120),
            ];
        }

        // If no replacements were made, don't save
        if ($totalReplacements === 0) {
            return $this->error(
                "No replacements were made. None of the find strings matched the template content. " .
                "Details: " . json_encode($results)
            );
        }

        // Validate the patched template
        $validationErrors = $this->validatePatchedTemplate($patchedTemplate);
        if (!empty($validationErrors)) {
            return $this->error(
                "The patched template has issues. Changes were NOT saved:\n- " . implode("\n- ", $validationErrors)
            );
        }

        // Re-discover field definitions from the patched template
        $newFieldDefs = Page::discoverFieldsFromTemplate($patchedTemplate);
        $newFieldKeys = array_column($newFieldDefs, 'key');
        $existingFieldKeys = array_keys($page->fields ?? []);

        // Only count fields that are ALSO in the current field_definitions as "dropped"
        // This avoids false positives from orphaned field data left over from previous templates
        $currentFieldDefKeys = array_column($page->field_definitions ?? [], 'key');
        $activeFieldKeys = array_intersect($existingFieldKeys, $currentFieldDefKeys);
        $droppedFields = array_diff($activeFieldKeys, $newFieldKeys);

        if (count($droppedFields) > 0) {
            Log::warning('Template patch dropped field keys', [
                'slug' => $slug,
                'dropped' => array_values($droppedFields),
            ]);
        }

        try {
            $page->update([
                'template' => 'custom',
                'custom_template' => $patchedTemplate,
                'field_definitions' => $newFieldDefs,
            ]);

            $message = "Template patched successfully. Made {$totalReplacements} replacement(s).";

            // Only warn about dropped fields if they're genuinely active fields
            if (count($droppedFields) > 0) {
                $message .= " WARNING: " . count($droppedFields) . " active field(s) were removed by this patch: " . implode(', ', array_values($droppedFields)) . ". This may indicate the patch was too aggressive.";
            }

            $message .= " Use render_page to verify the change looks correct.";

            return $this->success([
                'slug' => $page->slug,
                'title' => $page->title,
                'total_replacements' => $totalReplacements,
                'patch_results' => $results,
            ], $message);
        } catch (\Exception $e) {
            return $this->error("Failed to save patched template: {$e->getMessage()}");
        }
    }

    /**
     * Attempt fuzzy matching with progressively looser strategies.
     *
     * Returns ['template' => ..., 'count' => ..., 'match_type' => ..., 'note' => ...] or null.
     */
    protected function fuzzyReplace(string $template, string $find, string $replace): ?array
    {
        // Strategy 1: Normalize whitespace (collapse \n, \t, multiple spaces → single space)
        $result = $this->whitespaceNormalizedReplace($template, $find, $replace);
        if ($result !== null) {
            return array_merge($result, ['match_type' => 'whitespace_normalized']);
        }

        // Strategy 2: Handle HTML entity and quote variants
        $result = $this->quoteNormalizedReplace($template, $find, $replace);
        if ($result !== null) {
            return array_merge($result, ['match_type' => 'quote_normalized']);
        }

        // Strategy 3: Combined whitespace + quote normalization
        $result = $this->fullyNormalizedReplace($template, $find, $replace);
        if ($result !== null) {
            return array_merge($result, ['match_type' => 'fully_normalized']);
        }

        return null;
    }

    /**
     * Normalize whitespace in both find and template, then find the actual match
     * in the original template and replace it.
     */
    protected function whitespaceNormalizedReplace(string $template, string $find, string $replace): ?array
    {
        $normalizedFind = $this->normalizeWhitespace($find);
        $normalizedTemplate = $this->normalizeWhitespace($template);

        $pos = strpos($normalizedTemplate, $normalizedFind);
        if ($pos === false) {
            return null;
        }

        // Map the normalized position back to the original template
        $actualMatch = $this->findOriginalMatch($template, $normalizedFind, $pos);
        if ($actualMatch === null) {
            return null;
        }

        $count = 0;
        $result = $template;

        // Replace all occurrences by finding each one
        while (true) {
            $match = $this->findOriginalMatch($result, $normalizedFind);
            if ($match === null) break;

            $result = substr_replace($result, $replace, $match['start'], $match['length']);
            $count++;

            if ($count > 10) break; // Safety limit
        }

        return $count > 0 ? [
            'template' => $result,
            'count' => $count,
            'note' => 'Matched after normalizing whitespace differences.',
        ] : null;
    }

    /**
     * Handle quote and HTML entity variations.
     * Converts: &#39; → ', &quot; → ", \' → ', \" → "
     */
    protected function quoteNormalizedReplace(string $template, string $find, string $replace): ?array
    {
        $normalizedFind = $this->normalizeQuotes($find);
        $normalizedTemplate = $this->normalizeQuotes($template);

        $pos = strpos($normalizedTemplate, $normalizedFind);
        if ($pos === false) {
            return null;
        }

        $actualMatch = $this->findOriginalMatchQuotes($template, $normalizedFind);
        if ($actualMatch === null) {
            return null;
        }

        $result = substr_replace($template, $replace, $actualMatch['start'], $actualMatch['length']);

        return [
            'template' => $result,
            'count' => 1,
            'note' => 'Matched after normalizing quote/entity differences.',
        ];
    }

    /**
     * Combined whitespace + quote normalization.
     */
    protected function fullyNormalizedReplace(string $template, string $find, string $replace): ?array
    {
        $normalizedFind = $this->normalizeWhitespace($this->normalizeQuotes($find));
        $normalizedTemplate = $this->normalizeWhitespace($this->normalizeQuotes($template));

        $pos = strpos($normalizedTemplate, $normalizedFind);
        if ($pos === false) {
            return null;
        }

        // For fully normalized matches, we need to map back through both normalizations.
        // Use a regex-based approach: build a regex from the find string that allows flexible whitespace and quotes.
        $regex = $this->buildFlexibleRegex($find);
        if ($regex === null) {
            return null;
        }

        if (preg_match($regex, $template, $matches, PREG_OFFSET_CAPTURE)) {
            $matchedText = $matches[0][0];
            $matchStart = $matches[0][1];

            $result = substr_replace($template, $replace, $matchStart, strlen($matchedText));

            return [
                'template' => $result,
                'count' => 1,
                'note' => 'Matched after normalizing both whitespace and quote differences.',
            ];
        }

        return null;
    }

    /**
     * Find the original (un-normalized) substring in the template that corresponds
     * to a match in the normalized version.
     */
    protected function findOriginalMatch(string $template, string $normalizedFind, ?int $normalizedPos = null): ?array
    {
        // Build a regex that matches the find string with flexible whitespace
        $regex = $this->buildFlexibleRegex($normalizedFind, 'whitespace');
        if ($regex === null) {
            return null;
        }

        if (preg_match($regex, $template, $matches, PREG_OFFSET_CAPTURE)) {
            return [
                'start' => $matches[0][1],
                'length' => strlen($matches[0][0]),
                'text' => $matches[0][0],
            ];
        }

        return null;
    }

    /**
     * Find original match with quote normalization.
     */
    protected function findOriginalMatchQuotes(string $template, string $normalizedFind): ?array
    {
        $regex = $this->buildFlexibleRegex($normalizedFind, 'quotes');
        if ($regex === null) {
            return null;
        }

        if (preg_match($regex, $template, $matches, PREG_OFFSET_CAPTURE)) {
            return [
                'start' => $matches[0][1],
                'length' => strlen($matches[0][0]),
                'text' => $matches[0][0],
            ];
        }

        return null;
    }

    /**
     * Build a flexible regex from a find string.
     *
     * @param string $find The normalized find string
     * @param string $mode 'whitespace', 'quotes', or 'both'
     */
    protected function buildFlexibleRegex(string $find, string $mode = 'both'): ?string
    {
        // Escape regex special chars first
        $escaped = preg_quote($find, '/');

        if ($mode === 'whitespace' || $mode === 'both') {
            // Replace any whitespace sequence with \s+
            $escaped = preg_replace('/\s+/', '\\s+', $escaped);
        }

        if ($mode === 'quotes' || $mode === 'both') {
            // Allow any quote variant where we have a quote
            // Match ', ", &#39;, &quot;, \', \"
            $escaped = str_replace(
                [preg_quote("'", '/'), preg_quote('"', '/')],
                ["(?:'|&#39;|\\\\')", '(?:"|&quot;|\\\\")'],
                $escaped
            );
        }

        $regex = '/' . $escaped . '/s';

        // Test that the regex is valid
        if (@preg_match($regex, '') === false) {
            return null;
        }

        return $regex;
    }

    /**
     * Normalize whitespace: collapse all whitespace sequences to a single space.
     */
    protected function normalizeWhitespace(string $str): string
    {
        return preg_replace('/\s+/', ' ', trim($str));
    }

    /**
     * Normalize quotes and HTML entities.
     */
    protected function normalizeQuotes(string $str): string
    {
        $str = str_replace(['&#39;', '&apos;', "\\'"], "'", $str);
        $str = str_replace(['&quot;', '\\"'], '"', $str);
        $str = html_entity_decode($str, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        return $str;
    }

    /**
     * Try to find a partial match to give a helpful hint.
     */
    protected function findPartialMatch(string $template, string $find): ?string
    {
        // Try the first 40 chars of the find string
        $shortFind = mb_substr(trim($find), 0, 40);
        if (strlen($shortFind) > 10) {
            $normalizedShort = $this->normalizeWhitespace($shortFind);
            $normalizedTemplate = $this->normalizeWhitespace($template);

            if (strpos($normalizedTemplate, $normalizedShort) !== false) {
                return "The beginning of your find string was found in the template, but the full string didn't match. Try using a shorter, more precise find string — just the specific line or attribute you want to change.";
            }
        }

        // Try key identifiers from the find string (data-field values, class names)
        if (preg_match('/data-field="([^"]+)"/', $find, $m)) {
            if (strpos($template, 'data-field="' . $m[1] . '"') !== false) {
                return "Found data-field=\"{$m[1]}\" in the template, but the surrounding code doesn't match your find string. Use get_page_template to see the exact code around this field.";
            }
        }

        return null;
    }

    /**
     * Basic validation of the patched template.
     */
    protected function validatePatchedTemplate(string $template): array
    {
        $errors = [];

        if (strlen(trim($template)) < 50) {
            $errors[] = "Patched template is too short (" . strlen(trim($template)) . " chars). The patch may have deleted too much content.";
        }

        // Check for unmatched Blade directives
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

        return $errors;
    }

    public function captureRollbackData(array $params): array
    {
        $page = Page::where('slug', $params['slug'] ?? '')->first();
        if (!$page) {
            return [];
        }

        return [
            'slug' => $page->slug,
            'custom_template' => $page->custom_template,
            'field_definitions' => $page->field_definitions,
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
                'custom_template' => $rollbackData['custom_template'],
                'field_definitions' => $rollbackData['field_definitions'],
            ]);
            return true;
        } catch (\Exception $e) {
            Log::error('Template patch rollback failed', [
                'slug' => $rollbackData['slug'],
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }
}
