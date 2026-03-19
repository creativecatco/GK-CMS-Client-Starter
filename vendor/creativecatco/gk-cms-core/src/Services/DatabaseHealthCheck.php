<?php

namespace CreativeCatCo\GkCmsCore\Services;

use CreativeCatCo\GkCmsCore\Models\Page;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class DatabaseHealthCheck
{
    /**
     * Get all detected issues in the database.
     *
     * @return array An associative array of issue keys => issue details
     */
    public function getIssues(): array
    {
        $issues = [];

        try {
            $this->detectMissingSlugs($issues);
        } catch (\Exception $e) {
            $issues['missingSlugsError'] = [
                'label' => 'Missing Slugs Check Failed',
                'description' => 'Could not check for missing slugs: ' . $e->getMessage(),
                'count' => 0,
                'items' => [],
            ];
        }

        try {
            $this->detectMalformedTemplates($issues);
        } catch (\Exception $e) {
            $issues['malformedTemplatesError'] = [
                'label' => 'Malformed Templates Check Failed',
                'description' => 'Could not check for malformed templates: ' . $e->getMessage(),
                'count' => 0,
                'items' => [],
            ];
        }

        try {
            $this->detectEmptyFieldDefinitions($issues);
        } catch (\Exception $e) {
            // Silently skip if field_definitions column doesn't exist
        }

        try {
            $this->detectOrphanedPages($issues);
        } catch (\Exception $e) {
            // Silently skip
        }

        return $issues;
    }

    /**
     * Repair specific issues by their keys.
     *
     * @param array $issueKeys Array of issue keys to repair
     * @return array Results of each repair operation
     */
    public function repairIssues(array $issueKeys): array
    {
        $results = [];

        $repairMethods = [
            'missingSlugs' => 'repairMissingSlugs',
            'malformedTemplates' => 'repairMalformedTemplates',
            'emptyFieldDefinitions' => 'repairEmptyFieldDefinitions',
            'orphanedPages' => 'repairOrphanedPages',
        ];

        foreach ($issueKeys as $key) {
            if (isset($repairMethods[$key]) && method_exists($this, $repairMethods[$key])) {
                try {
                    $results[$key] = $this->{$repairMethods[$key]}();
                } catch (\Exception $e) {
                    $results[$key] = [
                        'repaired' => 0,
                        'total' => 0,
                        'error' => $e->getMessage(),
                    ];
                }
            }
        }

        return $results;
    }

    /**
     * Repair all detected issues at once.
     *
     * @return array Results of all repair operations
     */
    public function repairAll(): array
    {
        $issues = $this->getIssues();
        $issueKeys = array_keys($issues);

        // Filter out error entries
        $issueKeys = array_filter($issueKeys, fn($key) => !str_ends_with($key, 'Error'));

        return $this->repairIssues($issueKeys);
    }

    /**
     * Get a summary of the database health status.
     *
     * @return array ['healthy' => bool, 'issue_count' => int, 'issues' => [...]]
     */
    public function getSummary(): array
    {
        $issues = $this->getIssues();
        $totalIssues = 0;

        foreach ($issues as $issue) {
            $totalIssues += $issue['count'] ?? 0;
        }

        return [
            'healthy' => $totalIssues === 0,
            'issue_count' => $totalIssues,
            'issues' => $issues,
        ];
    }

    // ─── Detection Methods ───────────────────────────────────────────────

    /**
     * Detect pages with missing or empty slugs.
     */
    private function detectMissingSlugs(array &$issues): void
    {
        if (!Schema::hasTable('pages') || !Schema::hasColumn('pages', 'slug')) {
            return;
        }

        $pages = DB::table('pages')
            ->where(function ($query) {
                $query->whereNull('slug')
                    ->orWhere('slug', '');
            })
            ->select('id', 'title')
            ->get();

        if ($pages->isNotEmpty()) {
            $issues['missingSlugs'] = [
                'label' => 'Pages with Missing Slugs',
                'description' => 'These pages have a missing or empty slug, which can cause 500 errors when the page is accessed.',
                'count' => $pages->count(),
                'items' => $pages->pluck('title', 'id')->toArray(),
            ];
        }
    }

    /**
     * Detect pages with malformed custom templates.
     * Uses the `custom_template` column (the actual column name in the database).
     */
    private function detectMalformedTemplates(array &$issues): void
    {
        if (!Schema::hasTable('pages')) {
            return;
        }

        // Determine which column holds the custom template HTML
        $templateColumn = null;
        if (Schema::hasColumn('pages', 'custom_template')) {
            $templateColumn = 'custom_template';
        } elseif (Schema::hasColumn('pages', 'template_code')) {
            $templateColumn = 'template_code';
        }

        if (!$templateColumn) {
            return; // No custom template column found
        }

        $pages = DB::table('pages')
            ->whereNotNull($templateColumn)
            ->where($templateColumn, '!=', '')
            ->select('id', 'title', $templateColumn)
            ->get();

        $malformed = [];

        foreach ($pages as $page) {
            $template = $page->{$templateColumn};
            if ($this->isTemplateMalformed($template)) {
                $malformed[$page->id] = $page->title . ' (ID: ' . $page->id . ')';
            }
        }

        if (!empty($malformed)) {
            $issues['malformedTemplates'] = [
                'label' => 'Pages with Malformed Templates',
                'description' => 'These pages have custom template code that may cause rendering errors. The template contains potentially dangerous PHP code or appears to be corrupted.',
                'count' => count($malformed),
                'items' => $malformed,
            ];
        }
    }

    /**
     * Detect pages with empty or invalid field_definitions JSON.
     */
    private function detectEmptyFieldDefinitions(array &$issues): void
    {
        if (!Schema::hasTable('pages') || !Schema::hasColumn('pages', 'field_definitions')) {
            return;
        }

        // Find pages that have a custom_template but empty/null field_definitions
        $templateColumn = Schema::hasColumn('pages', 'custom_template') ? 'custom_template' : null;
        if (!$templateColumn) {
            return;
        }

        $pages = DB::table('pages')
            ->whereNotNull($templateColumn)
            ->where($templateColumn, '!=', '')
            ->where(function ($query) {
                $query->whereNull('field_definitions')
                    ->orWhere('field_definitions', '')
                    ->orWhere('field_definitions', '[]')
                    ->orWhere('field_definitions', 'null');
            })
            ->select('id', 'title')
            ->get();

        if ($pages->isNotEmpty()) {
            $issues['emptyFieldDefinitions'] = [
                'label' => 'Pages with Missing Field Definitions',
                'description' => 'These pages have a custom template but no field definitions. This may indicate the template was saved incorrectly.',
                'count' => $pages->count(),
                'items' => $pages->pluck('title', 'id')->toArray(),
            ];
        }
    }

    /**
     * Detect pages with invalid status values.
     */
    private function detectOrphanedPages(array &$issues): void
    {
        if (!Schema::hasTable('pages') || !Schema::hasColumn('pages', 'status')) {
            return;
        }

        $validStatuses = ['published', 'draft', 'archived'];

        $pages = DB::table('pages')
            ->whereNotIn('status', $validStatuses)
            ->select('id', 'title', 'status')
            ->get();

        if ($pages->isNotEmpty()) {
            $items = [];
            foreach ($pages as $page) {
                $items[$page->id] = "{$page->title} (status: {$page->status})";
            }

            $issues['orphanedPages'] = [
                'label' => 'Pages with Invalid Status',
                'description' => 'These pages have a status value that is not recognized by the CMS.',
                'count' => $pages->count(),
                'items' => $items,
            ];
        }
    }

    // ─── Repair Methods ─────────────────────────────────────────────────

    /**
     * Repair pages with missing slugs by generating them from the title.
     */
    private function repairMissingSlugs(): array
    {
        if (!Schema::hasTable('pages') || !Schema::hasColumn('pages', 'slug')) {
            return ['repaired' => 0, 'total' => 0, 'message' => 'Pages table or slug column not found.'];
        }

        $pages = DB::table('pages')
            ->where(function ($query) {
                $query->whereNull('slug')
                    ->orWhere('slug', '');
            })
            ->get();

        $repaired = 0;

        foreach ($pages as $page) {
            $baseSlug = Str::slug($page->title ?: 'page-' . $page->id);

            // Ensure uniqueness
            $slug = $baseSlug;
            $counter = 1;
            while (DB::table('pages')->where('slug', $slug)->where('id', '!=', $page->id)->exists()) {
                $slug = $baseSlug . '-' . $counter;
                $counter++;
            }

            DB::table('pages')->where('id', $page->id)->update(['slug' => $slug]);
            $repaired++;
        }

        return [
            'repaired' => $repaired,
            'total' => $pages->count(),
            'message' => "Repaired {$repaired} pages with missing slugs.",
        ];
    }

    /**
     * Repair malformed templates by resetting them to null (uses the default template).
     */
    private function repairMalformedTemplates(): array
    {
        $templateColumn = null;
        if (Schema::hasColumn('pages', 'custom_template')) {
            $templateColumn = 'custom_template';
        } elseif (Schema::hasColumn('pages', 'template_code')) {
            $templateColumn = 'template_code';
        }

        if (!$templateColumn) {
            return ['repaired' => 0, 'total' => 0, 'message' => 'No custom template column found.'];
        }

        $pages = DB::table('pages')
            ->whereNotNull($templateColumn)
            ->where($templateColumn, '!=', '')
            ->get();

        $repaired = 0;

        foreach ($pages as $page) {
            if ($this->isTemplateMalformed($page->{$templateColumn})) {
                DB::table('pages')->where('id', $page->id)->update([
                    $templateColumn => null,
                ]);
                $repaired++;
            }
        }

        return [
            'repaired' => $repaired,
            'total' => $pages->count(),
            'message' => "Reset {$repaired} malformed templates to default.",
        ];
    }

    /**
     * Repair pages with empty field definitions by re-discovering fields from the template.
     */
    private function repairEmptyFieldDefinitions(): array
    {
        $templateColumn = Schema::hasColumn('pages', 'custom_template') ? 'custom_template' : null;
        if (!$templateColumn || !Schema::hasColumn('pages', 'field_definitions')) {
            return ['repaired' => 0, 'total' => 0, 'message' => 'Required columns not found.'];
        }

        $pages = Page::whereNotNull($templateColumn)
            ->where($templateColumn, '!=', '')
            ->where(function ($query) {
                $query->whereNull('field_definitions')
                    ->orWhere('field_definitions', '')
                    ->orWhere('field_definitions', '[]')
                    ->orWhere('field_definitions', 'null');
            })
            ->get();

        $repaired = 0;

        foreach ($pages as $page) {
            try {
                // Re-discover fields from the template
                if (method_exists($page, 'discoverFieldsFromTemplate')) {
                    $page->discoverFieldsFromTemplate();
                    $page->save();
                    $repaired++;
                }
            } catch (\Exception $e) {
                // Skip pages that can't be repaired
                continue;
            }
        }

        return [
            'repaired' => $repaired,
            'total' => $pages->count(),
            'message' => "Re-discovered field definitions for {$repaired} pages.",
        ];
    }

    /**
     * Repair pages with invalid status by setting them to 'draft'.
     */
    private function repairOrphanedPages(): array
    {
        if (!Schema::hasColumn('pages', 'status')) {
            return ['repaired' => 0, 'total' => 0, 'message' => 'Status column not found.'];
        }

        $validStatuses = ['published', 'draft', 'archived'];

        $repaired = DB::table('pages')
            ->whereNotIn('status', $validStatuses)
            ->update(['status' => 'draft']);

        return [
            'repaired' => $repaired,
            'total' => $repaired,
            'message' => "Set {$repaired} pages with invalid status to 'draft'.",
        ];
    }

    // ─── Helper Methods ─────────────────────────────────────────────────

    /**
     * Check if a template string appears to be malformed.
     */
    private function isTemplateMalformed(?string $template): bool
    {
        if (empty($template)) {
            return false;
        }

        // Check for raw PHP code injection (dangerous)
        if (preg_match('/<\?php\s/', $template)) {
            return true;
        }

        // Check for unclosed Blade directives
        $openDirectives = preg_match_all('/@(if|foreach|for|while|section|push)\b/', $template);
        $closeDirectives = preg_match_all('/@(endif|endforeach|endfor|endwhile|endsection|endpush)\b/', $template);
        if ($openDirectives > 0 && $closeDirectives === 0) {
            return true;
        }

        // Check for severely truncated HTML (likely from a failed save)
        $length = strlen($template);
        if ($length > 100) {
            // Count opening and closing tags
            $openTags = preg_match_all('/<[a-zA-Z][^>]*>/', $template);
            $closeTags = preg_match_all('/<\/[a-zA-Z][^>]*>/', $template);

            // If there are many opening tags but very few closing tags, it's likely truncated
            if ($openTags > 5 && $closeTags < ($openTags * 0.3)) {
                return true;
            }
        }

        return false;
    }
}
