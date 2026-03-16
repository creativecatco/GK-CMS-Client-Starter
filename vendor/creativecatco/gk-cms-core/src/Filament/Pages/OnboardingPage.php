<?php

namespace CreativeCatCo\GkCmsCore\Filament\Pages;

use Filament\Pages\Page;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use CreativeCatCo\GkCmsCore\Models\Setting;

class OnboardingPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-rocket-launch';
    protected static ?string $navigationLabel = 'Setup Wizard';
    protected static ?string $title = 'Welcome to GKeys CMS';
    protected static ?string $slug = 'onboarding';
    protected static ?int $navigationSort = 0;

    protected static string $view = 'cms-core::filament.pages.onboarding';

    // Only show in nav if onboarding not complete
    public static function shouldRegisterNavigation(): bool
    {
        return !Setting::get('onboarding_complete', false);
    }

    public ?array $data = [];

    public int $step = 1;

    public function mount(): void
    {
        $this->data = [
            'company_name' => Setting::get('site_name', Setting::get('company_name', '')),
            'company_tagline' => Setting::get('tagline', Setting::get('company_tagline', '')),
            'company_email' => Setting::get('contact_email', Setting::get('admin_email', '')),
            'company_phone' => Setting::get('contact_phone', Setting::get('company_phone', '')),
            'company_address' => Setting::get('contact_address', Setting::get('company_address', '')),
            'logo' => Setting::get('logo', ''),
            'primary_color' => Setting::get('theme_primary_color', '#cfff2e'),
            'secondary_color' => Setting::get('theme_secondary_color', '#293726'),
            'font_heading' => Setting::get('theme_font_heading', 'Inter'),
            'font_body' => Setting::get('theme_font_body', 'Inter'),
            'smtp_enabled' => Setting::get('smtp_enabled', false),
            'smtp_host' => Setting::get('smtp_host', ''),
            'smtp_port' => Setting::get('smtp_port', '587'),
            'smtp_username' => Setting::get('smtp_username', ''),
            'smtp_password' => Setting::get('smtp_password', ''),
            'smtp_encryption' => Setting::get('smtp_encryption', 'tls'),
            // AI Setup
            'ai_provider' => Setting::get('ai_provider', 'openai'),
            'ai_api_key' => Setting::get('ai_api_key', ''),
            'ai_model' => Setting::get('ai_model', ''),
            'image_gen_provider' => Setting::get('image_gen_provider', 'auto'),
            'google_ai_api_key' => Setting::get('google_ai_api_key', ''),
            'openai_image_api_key' => Setting::get('openai_image_api_key', ''),
        ];
    }

    public function nextStep(): void
    {
        $this->step = min($this->step + 1, 5);
    }

    public function previousStep(): void
    {
        $this->step = max($this->step - 1, 1);
    }

    public function complete(): void
    {
        $d = $this->data;

        // Save company info to ALL the keys that the system reads from.
        // The Settings page reads: site_name, tagline, contact_email, contact_phone, contact_address
        // The header/footer templates read: company_name, site_name, logo
        // We write to all of them to ensure consistency.

        // General / Company
        Setting::set('site_name', $d['company_name'] ?? '', 'general');
        Setting::set('company_name', $d['company_name'] ?? '', 'general');
        Setting::set('tagline', $d['company_tagline'] ?? '', 'general');
        Setting::set('company_tagline', $d['company_tagline'] ?? '', 'general');

        // Contact
        Setting::set('contact_email', $d['company_email'] ?? '', 'contact');
        Setting::set('admin_email', $d['company_email'] ?? '', 'email');
        Setting::set('contact_phone', $d['company_phone'] ?? '', 'contact');
        Setting::set('company_phone', $d['company_phone'] ?? '', 'general');
        Setting::set('contact_address', $d['company_address'] ?? '', 'contact');
        Setting::set('company_address', $d['company_address'] ?? '', 'general');

        // Branding / Theme
        Setting::set('logo', $d['logo'] ?? '', 'general');
        Setting::set('theme_primary_color', $d['primary_color'] ?? '#cfff2e', 'theme');
        Setting::set('theme_secondary_color', $d['secondary_color'] ?? '#293726', 'theme');
        Setting::set('theme_font_heading', $d['font_heading'] ?? 'Inter', 'theme');
        Setting::set('theme_font_body', $d['font_body'] ?? 'Inter', 'theme');

        // SMTP
        Setting::set('smtp_enabled', $d['smtp_enabled'] ?? false, 'email');
        if ($d['smtp_enabled'] ?? false) {
            Setting::set('smtp_host', $d['smtp_host'] ?? '', 'email');
            Setting::set('smtp_port', $d['smtp_port'] ?? '587', 'email');
            Setting::set('smtp_username', $d['smtp_username'] ?? '', 'email');
            Setting::set('smtp_password', $d['smtp_password'] ?? '', 'email');
            Setting::set('smtp_encryption', $d['smtp_encryption'] ?? 'tls', 'email');
            Setting::set('mail_driver', 'smtp', 'email');
        }

        // AI Setup
        Setting::set('ai_provider', $d['ai_provider'] ?? 'openai', 'ai');
        Setting::set('ai_api_key', $d['ai_api_key'] ?? '', 'ai');
        Setting::set('ai_model', $d['ai_model'] ?? '', 'ai');
        Setting::set('image_gen_provider', $d['image_gen_provider'] ?? 'auto', 'ai');
        Setting::set('google_ai_api_key', $d['google_ai_api_key'] ?? '', 'ai');
        Setting::set('openai_image_api_key', $d['openai_image_api_key'] ?? '', 'ai');

        // Mark onboarding as complete
        Setting::set('onboarding_complete', true, 'general');

        // Flush all caches so changes take effect immediately
        Setting::flushCache();

        Notification::make()
            ->title('Setup Complete!')
            ->body('Your GKeys CMS is ready to use.')
            ->success()
            ->send();

        $this->redirect('/admin');
    }

    public function skip(): void
    {
        Setting::set('onboarding_complete', true, 'general');

        Notification::make()
            ->title('Setup Skipped')
            ->body('You can configure these settings later in Settings.')
            ->info()
            ->send();

        $this->redirect('/admin');
    }
}
