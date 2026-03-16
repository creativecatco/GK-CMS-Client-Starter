<?php

namespace CreativeCatCo\GkCmsCore\Services\Ai\Tools;

use CreativeCatCo\GkCmsCore\Models\Setting;

class UpdateThemeTool extends AbstractTool
{
    /**
     * Valid theme setting keys.
     */
    protected const VALID_KEYS = [
        'theme_primary_color',
        'theme_secondary_color',
        'theme_accent_color',
        'theme_text_color',
        'theme_bg_color',
        'theme_header_bg',
        'theme_footer_bg',
        'theme_font_heading',
        'theme_font_body',
    ];

    public function name(): string
    {
        return 'update_theme';
    }

    public function description(): string
    {
        return 'Update theme settings such as colors and fonts. Only the specified settings are changed; others are preserved. Colors should be hex values (e.g., "#1a1a2e"). Fonts should be Google Font names (e.g., "Inter", "Playfair Display").';
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'settings' => [
                    'type' => 'object',
                    'description' => 'Key-value pairs of theme settings to update. Valid keys: theme_primary_color, theme_secondary_color, theme_accent_color, theme_text_color, theme_bg_color, theme_header_bg, theme_footer_bg, theme_font_heading, theme_font_body.',
                    'additionalProperties' => true,
                ],
            ],
            'required' => ['settings'],
        ];
    }

    public function execute(array $params): array
    {
        $settings = $params['settings'] ?? [];

        if (empty($settings)) {
            return $this->error('No theme settings provided to update.');
        }

        // Validate keys
        $invalidKeys = array_diff(array_keys($settings), self::VALID_KEYS);
        if (!empty($invalidKeys)) {
            return $this->error(
                'Invalid theme setting keys: ' . implode(', ', $invalidKeys) .
                '. Valid keys are: ' . implode(', ', self::VALID_KEYS)
            );
        }

        $updated = [];
        foreach ($settings as $key => $value) {
            Setting::set($key, $value, 'theme');
            $updated[$key] = $value;
        }

        return $this->success([
            'updated' => $updated,
            'count' => count($updated),
        ], 'Updated ' . count($updated) . ' theme setting(s). Changes are reflected immediately on the site.');
    }

    public function captureRollbackData(array $params): array
    {
        $settings = $params['settings'] ?? [];
        $previousValues = [];

        foreach (array_keys($settings) as $key) {
            $previousValues[$key] = Setting::get($key, '');
        }

        return ['previous_values' => $previousValues];
    }

    public function rollback(array $rollbackData): bool
    {
        $previousValues = $rollbackData['previous_values'] ?? [];

        foreach ($previousValues as $key => $value) {
            Setting::set($key, $value, 'theme');
        }

        return !empty($previousValues);
    }
}
