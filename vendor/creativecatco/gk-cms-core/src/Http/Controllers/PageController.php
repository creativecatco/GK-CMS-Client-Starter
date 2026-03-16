<?php

namespace CreativeCatCo\GkCmsCore\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Illuminate\Support\Facades\Blade;
use CreativeCatCo\GkCmsCore\Models\Page;
use CreativeCatCo\GkCmsCore\Models\Setting;
use CreativeCatCo\GkCmsCore\Services\SeoService;

class PageController extends Controller
{
    public function __construct(
        protected SeoService $seoService
    ) {}

    /**
     * Display the home page using the home_page_id setting.
     * Falls back to slug 'home' if no setting is configured.
     */
    public function showHome(): View
    {
        $homePageId = Setting::get('home_page_id');

        if ($homePageId) {
            $page = Page::published()->find($homePageId);
            if ($page) {
                return $this->renderPage($page);
            }
        }

        // Fallback: look for a page with slug 'home'
        return $this->show('home');
    }

    /**
     * Display a page by its slug.
     *
     * Rendering priority:
     * 1. Custom template (AI-generated Blade stored in DB) with editable fields
     * 2. File-based template with editable fields
     * 3. Block-based rendering (legacy)
     * 4. Rich text content (legacy)
     */
    public function show(string $slug = 'home'): View
    {
        $page = Page::published()
            ->where('slug', $slug)
            ->first();

        $theme = config('cms.theme', 'theme');

        // If no page found, show a placeholder
        if (!$page) {
            $seo = $this->seoService->generateDefault();
            $view = $this->resolveView($theme, 'default');
            return view($view, [
                'page' => null,
                'seo' => $seo,
                'fields' => [],
                'renderedBlocks' => '',
            ]);
        }

        $seo = $this->seoService->generate($page);
        $fields = $page->fields ?? [];

        // Priority 1: Custom template stored in database
        if ($page->template === 'custom' && !empty($page->custom_template)) {
            return $this->renderCustomTemplate($page, $seo, $fields);
        }

        // Priority 2: File-based template with fields
        // Priority 3: Block-based rendering (legacy)
        $renderedBlocks = '';
        if ($page->hasBlocks()) {
            $renderedBlocks = $this->renderBlocks($page->blocks, $theme);
        }

        $template = $page->template ?? 'default';
        $view = $this->resolveView($theme, $template, $slug);

        return view($view, compact('page', 'seo', 'fields', 'renderedBlocks'));
    }

    /**
     * Render a specific Page model (used by showHome).
     */
    protected function renderPage(Page $page): View
    {
        $theme = config('cms.theme', 'theme');
        $seo = $this->seoService->generate($page);
        $fields = $page->fields ?? [];

        // Priority 1: Custom template stored in database
        if ($page->template === 'custom' && !empty($page->custom_template)) {
            return $this->renderCustomTemplate($page, $seo, $fields);
        }

        // Priority 2/3: File-based or block-based
        $renderedBlocks = '';
        if ($page->hasBlocks()) {
            $renderedBlocks = $this->renderBlocks($page->blocks, $theme);
        }

        $template = $page->template ?? 'default';
        $view = $this->resolveView($theme, $template, $page->slug);

        return view($view, compact('page', 'seo', 'fields', 'renderedBlocks'));
    }

    /**
     * Render a custom template stored in the database.
     * The template is compiled as Blade with $page, $fields, and $seo available.
     */
    protected function renderCustomTemplate(Page $page, array $seo, array $fields): View
    {
        $templateContent = $page->custom_template;

        // If the custom template doesn't extend a layout, wrap it in the default layout
        if (!str_contains($templateContent, '@extends')) {
            $templateContent = '@extends(\'cms-core::layouts.app\')' . "\n\n" .
                '@section(\'content\')' . "\n" .
                $templateContent . "\n" .
                '@endsection';
        }

        // Compile the Blade template from the string
        $compiled = Blade::render($templateContent, [
            'page' => $page,
            'fields' => $fields,
            'seo' => $seo,
            'f' => (object) $fields, // Shorthand: $f->hero_headline
        ]);

        // We need to return a View, so we render into a wrapper view
        return view('cms-core::pages.custom-render', [
            'page' => $page,
            'seo' => $seo,
            'fields' => $fields,
            'renderedContent' => $compiled,
        ]);
    }

    /**
     * Render all blocks for a page into HTML (legacy support).
     */
    protected function renderBlocks(array $blocks, string $theme): string
    {
        $html = '';

        foreach ($blocks as $index => $block) {
            $type = $block['type'] ?? '';
            if (empty($type)) {
                continue;
            }

            $themeView = "{$theme}.blocks.{$type}";
            $packageView = "cms-core::blocks.{$type}";

            $blockView = view()->exists($themeView) ? $themeView : $packageView;

            if (view()->exists($blockView)) {
                $html .= view($blockView, [
                    'block' => $block,
                    'loop' => (object) ['index' => $index, 'first' => $index === 0, 'last' => $index === count($blocks) - 1],
                ])->render();
            }
        }

        return $html;
    }

    /**
     * Resolve the Blade view path with fallback chain.
     */
    protected function resolveView(string $theme, string $template, ?string $slug = null): string
    {
        $candidates = [
            "{$theme}.pages.{$template}",
            "cms-core::pages.{$template}",
        ];

        if ($slug && $slug !== $template) {
            $candidates[] = "{$theme}.pages.{$slug}";
        }

        $candidates[] = "{$theme}.pages.default";
        $candidates[] = "cms-core::pages.default";

        foreach ($candidates as $candidate) {
            if (view()->exists($candidate)) {
                return $candidate;
            }
        }

        return 'cms-core::pages.default';
    }
}
