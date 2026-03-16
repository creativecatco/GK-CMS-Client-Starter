<?php

namespace CreativeCatCo\GkCmsCore\Services\Ai\Tools;

use CreativeCatCo\GkCmsCore\Models\Setting;

class UpdateSettingsTool extends AbstractTool
{
    /**
     * Valid site setting keys that the AI is allowed to modify.
     */
    protected const VALID_KEYS = [
        'site_name', 'tagline', 'company_name', 'company_email', 'company_phone',
        'company_address', 'contact_email', 'contact_phone', 'contact_address',
        'social_facebook', 'social_twitter', 'social_instagram', 'social_linkedin',
        'social_youtube', 'social_tiktok', 'social_pinterest', 'social_github',
        'home_page_id', 'enable_portfolio', 'enable_products', 'enable_blog',
        'footer_text', 'google_analytics_id',
    ];

    /**
     * Keys that the AI should NOT be able to modify (security-sensitive).
     */
    protected const PROTECTED_KEYS = [
        'ai_provider', 'ai_api_key', 'ai_model', 'ai_temperature', 'ai_max_tokens',
        'github_token', 'logo', 'favicon',
    ];

    public function name(): string
    {
        return 'update_settings';
    }

    public function description(): string
    {
        return 'Update site settings such as company info, contact details, social media links, and feature toggles. Only the specified settings are changed; others are preserved. Cannot modify AI settings, logo, or favicon.';
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'settings' => [
                    'type' => 'object',
                    'description' => 'Key-value pairs of settings to update. Common keys: site_name, tagline, company_name, company_email, company_phone, company_address, contact_email, contact_phone, social_facebook, social_instagram, social_linkedin, footer_text, home_page_id, enable_portfolio, enable_products.',
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
            return $this->error('No settings provided to update.');
        }

        // Check for protected keys
        $protectedAttempts = array_intersect(array_keys($settings), self::PROTECTED_KEYS);
        if (!empty($protectedAttempts)) {
            return $this->error(
                'Cannot modify protected settings: ' . implode(', ', $protectedAttempts) .
                '. These must be changed manually in the admin panel.'
            );
        }

        // Validate keys
        $invalidKeys = array_diff(array_keys($settings), self::VALID_KEYS);
        if (!empty($invalidKeys)) {
            return $this->error(
                'Invalid setting keys: ' . implode(', ', $invalidKeys) .
                '. Use get_settings to see available keys.'
            );
        }

        $updated = [];
        foreach ($settings as $key => $value) {
            $group = 'general';
            if (str_starts_with($key, 'social_')) {
                $group = 'social';
            } elseif (str_starts_with($key, 'contact_') || str_starts_with($key, 'company_')) {
                $group = 'contact';
            }

            Setting::set($key, $value, $group);
            $updated[$key] = $value;
        }

        return $this->success([
            'updated' => $updated,
            'count' => count($updated),
        ], 'Updated ' . count($updated) . ' site setting(s).');
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
            Setting::set($key, $value);
        }

        return !empty($previousValues);
    }
}
