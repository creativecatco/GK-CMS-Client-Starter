<?php

namespace CreativeCatCo\GkCmsCore\Services\Ai\Tools;

use CreativeCatCo\GkCmsCore\Models\Setting;

class GetSettingsTool extends AbstractTool
{
    public function name(): string
    {
        return 'get_settings';
    }

    public function description(): string
    {
        return 'Get the current site settings including company info, contact details, social media links, and feature toggles. These values are available in templates via the $settings array.';
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
        $siteKeys = [
            'site_name', 'tagline', 'company_name', 'company_email', 'company_phone',
            'company_address', 'contact_email', 'contact_phone', 'contact_address',
            'social_facebook', 'social_twitter', 'social_instagram', 'social_linkedin',
            'social_youtube', 'social_tiktok', 'social_pinterest', 'social_github',
            'home_page_id', 'enable_portfolio', 'enable_products', 'enable_blog',
            'logo', 'favicon', 'footer_text', 'google_analytics_id',
        ];

        $settings = [];
        foreach ($siteKeys as $key) {
            $val = Setting::get($key, '');
            if ($val !== '' && $val !== null) {
                $settings[$key] = $val;
            }
        }

        return $this->success([
            'settings' => $settings,
            'usage_note' => 'Access these in templates via $settings[\'key_name\']. Example: {{ $settings[\'site_name\'] }}',
        ], 'Site settings retrieved successfully.');
    }
}
