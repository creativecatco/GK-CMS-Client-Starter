<?php

namespace CreativeCatCo\GkCmsCore\Services\Ai;

use CreativeCatCo\GkCmsCore\Models\AiUserPreference;
use CreativeCatCo\GkCmsCore\Models\Page;
use CreativeCatCo\GkCmsCore\Models\Menu;
use CreativeCatCo\GkCmsCore\Models\Setting;

class SystemPromptLoader
{
    /**
     * Optional user ID for loading user-specific preferences.
     */
    protected ?int $userId = null;

    /**
     * Set the user ID for preference loading.
     */
    public function forUser(int $userId): self
    {
        $this->userId = $userId;
        return $this;
    }

    public function load(): string
    {
        $parts = [];

        // 1. Static documentation
        $parts[] = $this->getStaticPrompt();

        // 2. Dynamic site context
        $parts[] = $this->getDynamicContext();

        // 3. User preferences (conversation memory)
        $prefsContext = $this->getUserPreferences();
        if (!empty($prefsContext)) {
            $parts[] = $prefsContext;
        }

        return implode("\n\n---\n\n", $parts);
    }

    /**
     * Load saved user preferences to inject into the system prompt.
     * This gives the AI memory across conversations.
     */
    protected function getUserPreferences(): string
    {
        if (!$this->userId) {
            return '';
        }

        try {
            return AiUserPreference::getContextString($this->userId);
        } catch (\Exception $e) {
            // Table might not exist yet (pre-migration)
            return '';
        }
    }

    /**
     * Get the static system prompt from the bundled markdown file.
     * Falls back to a minimal prompt if the file doesn't exist.
     */
    protected function getStaticPrompt(): string
    {
        // Check for a published (customizable) version first
        $publishedPath = resource_path('cms/ai-system-prompt.md');
        if (file_exists($publishedPath)) {
            return file_get_contents($publishedPath);
        }

        // Fall back to the package's bundled version
        $packagePath = __DIR__ . '/../../../resources/prompts/system-prompt.md';
        if (file_exists($packagePath)) {
            return file_get_contents($packagePath);
        }

        // Absolute fallback — minimal prompt
        return $this->getMinimalPrompt();
    }

    /**
     * Build dynamic context from the current site state.
     * This gives the AI awareness of what already exists.
     */
    protected function getDynamicContext(): string
    {
        $context = "## Current Site State\n\n";
        $context .= "The following is the current state of this website. Use this information to understand what exists before making changes.\n\n";

        // Site URL
        $siteUrl = config('app.url', request()->getSchemeAndHttpHost());
        $context .= "### Site URL\n\n";
        $context .= "- **URL:** {$siteUrl}\n";
        $context .= "- Use this URL with `scan_website` when you need to analyze the live site.\n\n";

        // Pages
        $context .= "### Existing Pages\n\n";
        $pages = Page::orderBy('sort_order')->get();
        if ($pages->isEmpty()) {
            $context .= "No pages exist yet. The site is empty and needs to be built from scratch.\n\n";
        } else {
            $context .= "| Title | Slug | Type | Status | Has Custom Template |\n";
            $context .= "|-------|------|------|--------|--------------------|\n";
            foreach ($pages as $page) {
                $hasTemplate = !empty($page->custom_template) ? 'Yes' : 'No';
                $context .= "| {$page->title} | /{$page->slug} | {$page->page_type} | {$page->status} | {$hasTemplate} |\n";
            }
            $context .= "\n";
        }

        // Theme
        $context .= "### Current Theme\n\n";
        $themeKeys = [
            'theme_primary_color', 'theme_secondary_color', 'theme_accent_color',
            'theme_text_color', 'theme_bg_color', 'theme_header_bg', 'theme_footer_bg',
            'theme_font_heading', 'theme_font_body',
        ];
        foreach ($themeKeys as $key) {
            $val = Setting::get($key, '');
            if ($val) {
                $label = str_replace('theme_', '', $key);
                $label = str_replace('_', ' ', $label);
                $context .= "- **{$label}:** {$val}\n";
            }
        }
        $context .= "\n";

        // Site settings
        $context .= "### Site Settings\n\n";
        $settingKeys = [
            'site_name', 'tagline', 'company_name', 'company_email', 'company_phone',
            'company_address', 'contact_email', 'contact_phone',
        ];
        foreach ($settingKeys as $key) {
            $val = Setting::get($key, '');
            if ($val) {
                $label = str_replace('_', ' ', $key);
                $context .= "- **{$label}:** {$val}\n";
            }
        }
        $context .= "\n";

        // Menus
        $context .= "### Navigation Menus\n\n";
        $menus = Menu::all();
        if ($menus->isEmpty()) {
            $context .= "No menus configured yet.\n\n";
        } else {
            foreach ($menus as $menu) {
                $items = $menu->items ?? [];
                $itemCount = count($items);
                $context .= "- **{$menu->location}:** {$itemCount} item(s)";
                if ($itemCount > 0) {
                    $labels = array_map(fn($i) => $i['label'] ?? '?', $items);
                    $context .= " — " . implode(', ', $labels);
                }
                $context .= "\n";
            }
            $context .= "\n";
        }

        // Home page
        $homePageId = Setting::get('home_page_id');
        if ($homePageId) {
            $homePage = Page::find($homePageId);
            if ($homePage) {
                $context .= "### Home Page\n\n";
                $context .= "The home page is set to **\"{$homePage->title}\"** (slug: `{$homePage->slug}`).\n\n";
            }
        } else {
            $context .= "### Home Page\n\n";
            $context .= "No home page is set. You should create a home page and set it via `update_settings` with `home_page_id`.\n\n";
        }

        // Feature toggles
        $context .= "### Feature Toggles\n\n";
        $context .= "- **Portfolio:** " . (Setting::get('enable_portfolio', '0') ? 'Enabled' : 'Disabled') . "\n";
        $context .= "- **Products:** " . (Setting::get('enable_products', '0') ? 'Enabled' : 'Disabled') . "\n";
        $context .= "- **Blog:** " . (Setting::get('enable_blog', '1') ? 'Enabled' : 'Disabled') . "\n";

        return $context;
    }

    /**
     * Minimal fallback prompt if no markdown file is found.
     */
    protected function getMinimalPrompt(): string
    {
        return <<<'PROMPT'
You are an AI website builder integrated into GKeys CMS, a Laravel-based content management system.

Your job is to create and modify websites by generating Blade templates, configuring settings, and managing content through the provided tools.

Key rules:
1. Every editable element must have `data-field` and `data-field-type` attributes.
2. Never hardcode colors — use CSS variables: var(--color-primary), var(--color-secondary), etc.
3. Always provide default values using the ?? operator.
4. Use Tailwind CSS for layout and styling.
5. Templates should be responsive (mobile-first).
6. Use semantic HTML for accessibility.

Available CSS variables:
- var(--color-primary) — Primary brand color
- var(--color-secondary) — Secondary color
- var(--color-accent) — Accent color
- var(--color-text) — Body text color
- var(--color-bg) — Page background
- var(--font-heading) — Heading font
- var(--font-body) — Body font

Use the tools provided to create pages, update templates, manage settings, and build the website.
PROMPT;
    }
}
