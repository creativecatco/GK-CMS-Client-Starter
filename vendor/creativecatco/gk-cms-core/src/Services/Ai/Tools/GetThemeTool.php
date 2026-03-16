<?php

namespace CreativeCatCo\GkCmsCore\Services\Ai\Tools;

use CreativeCatCo\GkCmsCore\Models\Setting;

class GetThemeTool extends AbstractTool
{
    public function name(): string
    {
        return 'get_theme';
    }

    public function description(): string
    {
        return 'Get the current theme settings including colors, fonts, and other visual configuration. These values are available as CSS variables in templates (e.g., var(--color-primary), var(--font-heading)).';
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => new \stdClass(),
            'required' => [],
        ];
    }

    public function execute(array $params): array
    {
        $themeKeys = [
            'theme_primary_color' => 'var(--color-primary)',
            'theme_secondary_color' => 'var(--color-secondary)',
            'theme_accent_color' => 'var(--color-accent)',
            'theme_text_color' => 'var(--color-text)',
            'theme_bg_color' => 'var(--color-bg)',
            'theme_header_bg' => 'var(--header-bg)',
            'theme_footer_bg' => 'var(--footer-bg)',
            'theme_font_heading' => 'var(--font-heading)',
            'theme_font_body' => 'var(--font-body)',
        ];

        $theme = [];
        foreach ($themeKeys as $key => $cssVar) {
            $theme[$key] = [
                'value' => Setting::get($key, ''),
                'css_variable' => $cssVar,
            ];
        }

        return $this->success([
            'theme' => $theme,
            'usage_note' => 'Use CSS variables in templates instead of hardcoding colors/fonts. Example: style="color: var(--color-primary); font-family: var(--font-heading);"',
        ], 'Theme settings retrieved successfully.');
    }
}
