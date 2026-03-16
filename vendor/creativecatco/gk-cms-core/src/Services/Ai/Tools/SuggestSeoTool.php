<?php

namespace CreativeCatCo\GkCmsCore\Services\Ai\Tools;

use CreativeCatCo\GkCmsCore\Models\Page;
use CreativeCatCo\GkCmsCore\Models\Setting;

class SuggestSeoTool extends AbstractTool
{
    public function name(): string
    {
        return 'suggest_seo';
    }

    public function description(): string
    {
        return 'Analyze a page and return its current SEO metadata along with the page content summary, so you can suggest improved SEO titles and descriptions. Also allows updating the SEO metadata directly. Use this after creating pages or when the user asks to improve SEO.';
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'slug' => [
                    'type' => 'string',
                    'description' => 'The page slug to analyze (e.g., "about", "services").',
                ],
                'action' => [
                    'type' => 'string',
                    'enum' => ['analyze', 'update'],
                    'description' => '"analyze" to get current SEO data and page content for analysis. "update" to set new SEO metadata. Defaults to "analyze".',
                ],
                'seo_title' => [
                    'type' => 'string',
                    'description' => 'New SEO title to set (only used with action "update"). Should be 50-60 characters, include primary keyword, and be compelling.',
                ],
                'seo_description' => [
                    'type' => 'string',
                    'description' => 'New SEO meta description to set (only used with action "update"). Should be 150-160 characters, include a call to action, and summarize the page.',
                ],
                'seo_keywords' => [
                    'type' => 'string',
                    'description' => 'Comma-separated keywords for the page (only used with action "update").',
                ],
            ],
            'required' => ['slug'],
        ];
    }

    public function execute(array $params): array
    {
        $slug = $params['slug'] ?? '';
        $action = $params['action'] ?? 'analyze';

        if (empty($slug)) {
            return $this->error('A page slug is required.');
        }

        $page = Page::where('slug', $slug)->first();
        if (!$page) {
            return $this->error("Page with slug '{$slug}' not found.");
        }

        if ($action === 'update') {
            return $this->updateSeo($page, $params);
        }

        return $this->analyzeSeo($page);
    }

    protected function analyzeSeo(Page $page): array
    {
        // Get the page content from fields
        $fields = $page->fields ?? [];
        $contentParts = [];

        foreach ($fields as $key => $value) {
            if (is_string($value) && strlen($value) > 5) {
                // Skip image paths and very short values
                if (!preg_match('/\.(jpg|jpeg|png|gif|svg|webp)$/i', $value)) {
                    $cleanValue = strip_tags($value);
                    if (strlen($cleanValue) > 10) {
                        $contentParts[$key] = mb_substr($cleanValue, 0, 200);
                    }
                }
            }
        }

        // Get company info for context
        $companyName = Setting::get('site_name', Setting::get('company_name', ''));
        $tagline = Setting::get('tagline', '');

        return $this->success([
            'page_title' => $page->title,
            'page_slug' => $page->slug,
            'page_url' => '/' . $page->slug,
            'current_seo_title' => $page->seo_title ?: '(not set — will use page title)',
            'current_seo_description' => $page->seo_description ?: '(not set)',
            'current_seo_keywords' => $page->seo_keywords ?: '(not set)',
            'company_name' => $companyName,
            'company_tagline' => $tagline,
            'content_summary' => $contentParts,
            'content_field_count' => count($fields),
            'template' => $page->template,
            'seo_tips' => [
                'title' => 'Should be 50-60 characters, include primary keyword near the beginning, include brand name',
                'description' => 'Should be 150-160 characters, include a call to action, summarize the page value proposition',
                'keywords' => 'Include 3-5 relevant keywords, separated by commas',
            ],
        ], "SEO analysis for '{$page->title}'. Review the current metadata and content summary, then use action 'update' to set optimized SEO data.");
    }

    protected function updateSeo(Page $page, array $params): array
    {
        $updated = [];

        if (isset($params['seo_title'])) {
            $page->seo_title = $params['seo_title'];
            $updated['seo_title'] = $params['seo_title'];
        }

        if (isset($params['seo_description'])) {
            $page->seo_description = $params['seo_description'];
            $updated['seo_description'] = $params['seo_description'];
        }

        if (isset($params['seo_keywords'])) {
            $page->seo_keywords = $params['seo_keywords'];
            $updated['seo_keywords'] = $params['seo_keywords'];
        }

        if (empty($updated)) {
            return $this->error('No SEO fields provided to update. Include seo_title, seo_description, or seo_keywords.');
        }

        try {
            $page->save();

            return $this->success([
                'page' => $page->title,
                'slug' => $page->slug,
                'updated_fields' => $updated,
            ], "SEO metadata updated for '{$page->title}'.");
        } catch (\Exception $e) {
            return $this->error("Failed to update SEO: {$e->getMessage()}");
        }
    }
}
