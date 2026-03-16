<?php

namespace CreativeCatCo\GkCmsCore\Filament\Pages;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use CreativeCatCo\GkCmsCore\Models\Setting;
use CreativeCatCo\GkCmsCore\Models\Page as CmsPage;

class SettingPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationGroup = 'Administration';

    protected static ?int $navigationSort = 11;

    protected static ?string $title = 'Site Settings';

    protected static ?string $slug = 'settings';

    protected static string $view = 'cms-core::filament.pages.settings';

    public ?array $data = [];

    public function mount(): void
    {
        $this->data = [
            // General
            'site_name' => Setting::get('site_name', config('cms.site_name')),
            'tagline' => Setting::get('tagline', ''),
            'show_tagline_header' => (bool) Setting::get('show_tagline_header', true),
            'logo' => Setting::get('logo', ''),
            'favicon' => Setting::get('favicon', ''),
            'home_page_id' => Setting::get('home_page_id', ''),

            // Email
            'admin_email' => Setting::get('admin_email', ''),
            'mail_driver' => Setting::get('mail_driver', 'smtp'),
            'smtp_host' => Setting::get('smtp_host', ''),
            'smtp_port' => Setting::get('smtp_port', '587'),
            'smtp_username' => Setting::get('smtp_username', ''),
            'smtp_password' => Setting::get('smtp_password', ''),
            'smtp_encryption' => Setting::get('smtp_encryption', 'tls'),
            'mail_from_name' => Setting::get('mail_from_name', ''),
            'mail_from_address' => Setting::get('mail_from_address', ''),

            // Contact
            'contact_email' => Setting::get('contact_email', ''),
            'contact_phone' => Setting::get('contact_phone', ''),
            'contact_address' => Setting::get('contact_address', ''),

            // Social
            'social_facebook' => Setting::get('social_facebook', ''),
            'social_twitter' => Setting::get('social_twitter', ''),
            'social_instagram' => Setting::get('social_instagram', ''),
            'social_linkedin' => Setting::get('social_linkedin', ''),
            'social_youtube' => Setting::get('social_youtube', ''),
            'social_tiktok' => Setting::get('social_tiktok', ''),

            // Analytics
            'google_analytics_id' => Setting::get('google_analytics_id', ''),

            // GoHighLevel
            'ghl_tracking_id' => Setting::get('ghl_tracking_id', ''),
            'ghl_tracking_domain' => Setting::get('ghl_tracking_domain', ''),

            // Custom Code
            'custom_head_code' => Setting::get('custom_head_code', ''),
            'custom_body_code' => Setting::get('custom_body_code', ''),

            // Theme Variables
            'theme_primary_color' => Setting::get('theme_primary_color', '#cfff2e'),
            'theme_secondary_color' => Setting::get('theme_secondary_color', '#293726'),
            'theme_header_bg' => Setting::get('theme_header_bg', '#15171e'),
            'theme_footer_bg' => Setting::get('theme_footer_bg', '#15171e'),
            'theme_font_heading' => Setting::get('theme_font_heading', 'Inter'),
            'theme_font_body' => Setting::get('theme_font_body', 'Inter'),
            'custom_font_embed' => Setting::get('custom_font_embed', ''),

            // Global CSS
            'global_css' => Setting::get('global_css', ''),

            // Post Types
            'enable_portfolio' => (bool) Setting::get('enable_portfolio', true),
            'enable_products' => (bool) Setting::get('enable_products', true),

            // AI Assistant
            'ai_provider' => Setting::get('ai_provider', 'openai'),
            'ai_api_key' => Setting::get('ai_api_key', ''),
            'ai_model' => Setting::get('ai_model', ''),
            'ai_temperature' => (float) Setting::get('ai_temperature', 0.7),
            'ai_max_tokens' => (int) Setting::get('ai_max_tokens', 16000),

            // Image Generation
            'image_gen_provider' => Setting::get('image_gen_provider', 'auto'),
            'google_ai_api_key' => Setting::get('google_ai_api_key', ''),
            'openai_image_api_key' => Setting::get('openai_image_api_key', ''),

            // GitHub
            'github_token' => Setting::get('github_token', ''),
        ];

        $this->form->fill($this->data);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Tabs::make('Settings')
                    ->tabs([
                        Forms\Components\Tabs\Tab::make('General')
                            ->icon('heroicon-o-globe-alt')
                            ->schema([
                                Forms\Components\TextInput::make('site_name')
                                    ->label('Site Name')
                                    ->required()
                                    ->maxLength(255),

                                Forms\Components\TextInput::make('tagline')
                                    ->label('Tagline')
                                    ->maxLength(255)
                                    ->helperText('A short description shown next to the site name/logo in the header.'),

                                Forms\Components\Toggle::make('show_tagline_header')
                                    ->label('Show Tagline in Header')
                                    ->helperText('When enabled, the tagline appears next to the logo/site name in the header.')
                                    ->default(true),

                                Forms\Components\FileUpload::make('logo')
                                    ->label('Logo')
                                    ->image()
                                    ->directory(config('cms.media_upload_path', 'cms/media') . '/branding')
                                    ->helperText('When a logo is uploaded, the site title text will be hidden and only the logo image will display. Recommended: SVG or PNG with transparent background.'),

                                Forms\Components\FileUpload::make('favicon')
                                    ->label('Favicon')
                                    ->directory(config('cms.media_upload_path', 'cms/media') . '/branding')
                                    ->acceptedFileTypes(['image/x-icon', 'image/png', 'image/svg+xml', 'image/vnd.microsoft.icon', 'image/ico', 'image/webp'])
                                    ->helperText('Upload a .ico, .png, or .svg favicon. Recommended size: 32x32 or 64x64 pixels.'),

                                Forms\Components\Select::make('home_page_id')
                                    ->label('Home Page')
                                    ->options(fn () => CmsPage::published()
                                        ->where('page_type', 'page')
                                        ->orderBy('title')
                                        ->pluck('title', 'id')
                                        ->toArray()
                                    )
                                    ->placeholder('Select a page to use as the home page')
                                    ->helperText('Choose which page is displayed when visitors go to your site root URL (/).'),
                            ]),

                        Forms\Components\Tabs\Tab::make('Email & SMTP')
                            ->icon('heroicon-o-envelope')
                            ->schema([
                                Forms\Components\Section::make('Admin Notifications')
                                    ->description('Email address that receives system notifications (form submissions, updates, etc.)')
                                    ->schema([
                                        Forms\Components\TextInput::make('admin_email')
                                            ->label('Admin Email Address')
                                            ->email()
                                            ->maxLength(255)
                                            ->placeholder('admin@yourdomain.com')
                                            ->helperText('All CMS notifications will be sent to this address.'),
                                    ]),

                                Forms\Components\Section::make('SMTP Configuration')
                                    ->description('Configure your email sending provider. Works with smtp.com, GoHighLevel, SendGrid, Mailgun, Amazon SES, or any SMTP provider.')
                                    ->schema([
                                        Forms\Components\Select::make('mail_driver')
                                            ->label('Mail Driver')
                                            ->options([
                                                'smtp' => 'SMTP',
                                                'sendmail' => 'Sendmail (server default)',
                                                'log' => 'Log (testing only)',
                                            ])
                                            ->default('smtp')
                                            ->live(),

                                        Forms\Components\TextInput::make('smtp_host')
                                            ->label('SMTP Host')
                                            ->maxLength(255)
                                            ->placeholder('smtp.gmail.com or smtp.smtp.com')
                                            ->visible(fn (Forms\Get $get) => $get('mail_driver') === 'smtp'),

                                        Forms\Components\TextInput::make('smtp_port')
                                            ->label('SMTP Port')
                                            ->maxLength(10)
                                            ->placeholder('587')
                                            ->visible(fn (Forms\Get $get) => $get('mail_driver') === 'smtp'),

                                        Forms\Components\TextInput::make('smtp_username')
                                            ->label('SMTP Username')
                                            ->maxLength(255)
                                            ->visible(fn (Forms\Get $get) => $get('mail_driver') === 'smtp'),

                                        Forms\Components\TextInput::make('smtp_password')
                                            ->label('SMTP Password')
                                            ->password()
                                            ->maxLength(255)
                                            ->visible(fn (Forms\Get $get) => $get('mail_driver') === 'smtp'),

                                        Forms\Components\Select::make('smtp_encryption')
                                            ->label('Encryption')
                                            ->options([
                                                'tls' => 'TLS (recommended)',
                                                'ssl' => 'SSL',
                                                '' => 'None',
                                            ])
                                            ->default('tls')
                                            ->visible(fn (Forms\Get $get) => $get('mail_driver') === 'smtp'),

                                        Forms\Components\TextInput::make('mail_from_name')
                                            ->label('From Name')
                                            ->maxLength(255)
                                            ->placeholder('My Website'),

                                        Forms\Components\TextInput::make('mail_from_address')
                                            ->label('From Email Address')
                                            ->email()
                                            ->maxLength(255)
                                            ->placeholder('noreply@yourdomain.com'),
                                    ]),
                            ]),

                        Forms\Components\Tabs\Tab::make('Contact')
                            ->icon('heroicon-o-phone')
                            ->schema([
                                Forms\Components\TextInput::make('contact_email')
                                    ->label('Public Email Address')
                                    ->email()
                                    ->maxLength(255),

                                Forms\Components\TextInput::make('contact_phone')
                                    ->label('Phone Number')
                                    ->tel()
                                    ->maxLength(50),

                                Forms\Components\Textarea::make('contact_address')
                                    ->label('Address')
                                    ->rows(3)
                                    ->maxLength(500),
                            ]),

                        Forms\Components\Tabs\Tab::make('Social')
                            ->icon('heroicon-o-share')
                            ->schema([
                                Forms\Components\TextInput::make('social_facebook')
                                    ->label('Facebook URL')
                                    ->url()
                                    ->maxLength(255)
                                    ->prefix('https://'),

                                Forms\Components\TextInput::make('social_twitter')
                                    ->label('Twitter / X URL')
                                    ->url()
                                    ->maxLength(255)
                                    ->prefix('https://'),

                                Forms\Components\TextInput::make('social_instagram')
                                    ->label('Instagram URL')
                                    ->url()
                                    ->maxLength(255)
                                    ->prefix('https://'),

                                Forms\Components\TextInput::make('social_linkedin')
                                    ->label('LinkedIn URL')
                                    ->url()
                                    ->maxLength(255)
                                    ->prefix('https://'),

                                Forms\Components\TextInput::make('social_youtube')
                                    ->label('YouTube URL')
                                    ->url()
                                    ->maxLength(255)
                                    ->prefix('https://'),

                                Forms\Components\TextInput::make('social_tiktok')
                                    ->label('TikTok URL')
                                    ->url()
                                    ->maxLength(255)
                                    ->prefix('https://'),
                            ]),

                        Forms\Components\Tabs\Tab::make('Theme')
                            ->icon('heroicon-o-paint-brush')
                            ->schema([
                                Forms\Components\Section::make('Brand Colors')
                                    ->schema([
                                        Forms\Components\ColorPicker::make('theme_primary_color')
                                            ->label('Primary / Accent Color'),

                                        Forms\Components\ColorPicker::make('theme_secondary_color')
                                            ->label('Secondary Color'),

                                        Forms\Components\ColorPicker::make('theme_header_bg')
                                            ->label('Header Background'),

                                        Forms\Components\ColorPicker::make('theme_footer_bg')
                                            ->label('Footer Background'),
                                    ])->columns(2),

                                Forms\Components\Section::make('Typography')
                                    ->schema([
                                        Forms\Components\Select::make('theme_font_heading')
                                            ->label('Heading Font')
                                            ->options(static::getFontOptions())
                                            ->searchable(),

                                        Forms\Components\Select::make('theme_font_body')
                                            ->label('Body Font')
                                            ->options(static::getFontOptions())
                                            ->searchable(),

                                        Forms\Components\Textarea::make('custom_font_embed')
                                            ->label('Custom Font Embed Code')
                                            ->rows(4)
                                            ->placeholder('<link href="https://fonts.googleapis.com/css2?family=YourFont&display=swap" rel="stylesheet">')
                                            ->helperText('Paste Google Fonts, Adobe Fonts, or any custom font embed code here. These fonts will then appear in the font selectors above and in the frontend theme editor.')
                                            ->columnSpanFull(),
                                    ])->columns(2),

                                Forms\Components\Section::make('Global CSS')
                                    ->description('Custom CSS applied site-wide. Use CSS variables like var(--primary-color) for theme colors.')
                                    ->schema([
                                        Forms\Components\Textarea::make('global_css')
                                            ->label('')
                                            ->rows(15)
                                            ->placeholder('/* Add your custom CSS here */'),
                                    ]),
                            ]),

                        Forms\Components\Tabs\Tab::make('Integrations')
                            ->icon('heroicon-o-puzzle-piece')
                            ->schema([
                                Forms\Components\Section::make('GoHighLevel CRM')
                                    ->description('Connect your GoHighLevel account to track page visits and capture form leads automatically.')
                                    ->schema([
                                        Forms\Components\TextInput::make('ghl_tracking_id')
                                            ->label('GHL Tracking ID')
                                            ->maxLength(100)
                                            ->placeholder('tk_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx')
                                            ->helperText('Find this in GHL: Settings > Business Profile > External Tracking Code.'),

                                        Forms\Components\TextInput::make('ghl_tracking_domain')
                                            ->label('GHL Tracking Domain (optional)')
                                            ->url()
                                            ->maxLength(255)
                                            ->placeholder('https://link.yourdomain.com'),
                                    ]),

                                Forms\Components\Section::make('Google Analytics')
                                    ->schema([
                                        Forms\Components\TextInput::make('google_analytics_id')
                                            ->label('Google Analytics ID')
                                            ->maxLength(50)
                                            ->placeholder('G-XXXXXXXXXX'),
                                    ]),
                            ]),

                        Forms\Components\Tabs\Tab::make('Post Types')
                            ->icon('heroicon-o-squares-plus')
                            ->schema([
                                Forms\Components\Section::make('Content Types')
                                    ->description('Enable or disable additional content types. Disabling a type hides it from the admin sidebar and removes its frontend routes.')
                                    ->schema([
                                        Forms\Components\Toggle::make('enable_portfolio')
                                            ->label('Enable Portfolio')
                                            ->helperText('Adds a Portfolio section with archive grid at /portfolio and individual project pages.')
                                            ->default(true),

                                        Forms\Components\Toggle::make('enable_products')
                                            ->label('Enable Products')
                                            ->helperText('Adds a Products section with archive grid at /products and individual product pages with pricing.')
                                            ->default(true),
                                    ]),
                            ]),

                           Forms\Components\Tabs\Tab::make('AI Assistant')
                            ->icon('heroicon-o-cpu-chip')
                            ->schema([
                                Forms\Components\Section::make('AI Provider Configuration')
                                    ->description('Configure the AI provider that powers the website builder assistant. You must provide your own API key.')
                                    ->schema([
                                        Forms\Components\Select::make('ai_provider')
                                            ->label('AI Provider')
                                            ->options([
                                                'openai' => 'OpenAI (GPT-4.1, GPT-4.1-mini)',
                                                'anthropic' => 'Anthropic (Claude)',
                                                'google' => 'Google (Gemini)',
                                                'xai' => 'xAI (Grok)',
                                                'manus' => 'Manus',
                                            ])
                                            ->default('openai')
                                            ->live()
                                            ->helperText('Select which AI provider to use. Each provider requires its own API key.'),

                                        Forms\Components\TextInput::make('ai_api_key')
                                            ->label('API Key')
                                            ->password()
                                            ->revealable()
                                            ->maxLength(500)
                                            ->placeholder(fn (Forms\Get $get) => match ($get('ai_provider')) {
                                                'openai' => 'sk-...',
                                                'anthropic' => 'sk-ant-...',
                                                'google' => 'AIza...',
                                                'xai' => 'xai-...',
                                                'manus' => 'manus-...',
                                                default => 'Enter your API key',
                                            })
                                            ->helperText(fn (Forms\Get $get) => match ($get('ai_provider')) {
                                                'openai' => 'Get your key at platform.openai.com/api-keys',
                                                'anthropic' => 'Get your key at console.anthropic.com/settings/keys',
                                                'google' => 'Get your key at aistudio.google.com/apikey',
                                                'xai' => 'Get your key at console.x.ai',
                                                'manus' => 'Get your key from your Manus dashboard',
                                                default => '',
                                            }),

                                        Forms\Components\Select::make('ai_model')
                                            ->label('Model')
                                            ->options(fn (Forms\Get $get) => match ($get('ai_provider')) {
                                                'openai' => [
                                                    'gpt-4.1' => 'GPT-4.1 (Most capable)',
                                                    'gpt-4.1-mini' => 'GPT-4.1 Mini (Fast & affordable)',
                                                    'gpt-4.1-nano' => 'GPT-4.1 Nano (Fastest)',
                                                    'gpt-4o' => 'GPT-4o',
                                                    'gpt-4o-mini' => 'GPT-4o Mini',
                                                ],
                                                'anthropic' => [
                                                    'claude-sonnet-4-20250514' => 'Claude Sonnet 4 (Latest)',
                                                    'claude-3-5-sonnet-20241022' => 'Claude 3.5 Sonnet',
                                                    'claude-3-5-haiku-20241022' => 'Claude 3.5 Haiku (Fast)',
                                                ],
                                                'google' => [
                                                    'gemini-2.5-pro' => 'Gemini 2.5 Pro (Most capable)',
                                                    'gemini-2.5-flash' => 'Gemini 2.5 Flash (Fast)',
                                                    'gemini-2.0-flash' => 'Gemini 2.0 Flash',
                                                ],
                                                'xai' => [
                                                    'grok-3' => 'Grok 3 (Most capable)',
                                                    'grok-3-mini' => 'Grok 3 Mini (Fast)',
                                                ],
                                                'manus' => [
                                                    'manus-1' => 'Manus 1',
                                                ],
                                                default => [],
                                            })
                                            ->live()
                                            ->helperText('Choose the specific model. More capable models produce better results but cost more.'),
                                    ])->columns(1),

                                Forms\Components\Section::make('Advanced Settings')
                                    ->description('Fine-tune the AI behavior. Default values work well for most use cases.')
                                    ->collapsed()
                                    ->schema([
                                        Forms\Components\TextInput::make('ai_temperature')
                                            ->label('Temperature')
                                            ->numeric()
                                            ->minValue(0)
                                            ->maxValue(1)
                                            ->step(0.1)
                                            ->default(0.7)
                                            ->helperText('Controls creativity. Lower = more predictable, Higher = more creative. Recommended: 0.7'),

                                        Forms\Components\TextInput::make('ai_max_tokens')
                                            ->label('Max Output Tokens')
                                            ->numeric()
                                            ->minValue(1024)
                                            ->maxValue(32768)
                                            ->default(4096)
                                            ->helperText('Maximum length of AI responses. Higher values allow more complex pages but cost more. Recommended: 4096'),
                                    ])->columns(2),

                                Forms\Components\Section::make('Image Generation')
                                    ->description('Configure AI image generation for creating custom visuals. This is separate from the LLM provider above. If not configured, the AI will skip image generation.')
                                    ->schema([
                                        Forms\Components\Select::make('image_gen_provider')
                                            ->label('Image Generation Provider')
                                            ->options([
                                                'auto' => 'Auto (use best available)',
                                                'nano_banana' => 'Nano Banana (Google Gemini) — Best for illustrations & artistic styles',
                                                'dalle' => 'OpenAI DALL-E 3 — Best for photorealistic images',
                                                'none' => 'Disabled — No image generation',
                                            ])
                                            ->default('auto')
                                            ->live()
                                            ->helperText('Choose which AI generates images. "Auto" will use whichever has a valid API key configured (prefers Nano Banana).'),

                                        Forms\Components\TextInput::make('google_ai_api_key')
                                            ->label('Google AI API Key (for Nano Banana)')
                                            ->password()
                                            ->revealable()
                                            ->maxLength(500)
                                            ->placeholder('AIza...')
                                            ->helperText('Get your key at aistudio.google.com/apikey — This is the same key used if your LLM provider is Google Gemini.')
                                            ->visible(fn (Forms\Get $get) => in_array($get('image_gen_provider'), ['auto', 'nano_banana'])),

                                        Forms\Components\TextInput::make('openai_image_api_key')
                                            ->label('OpenAI API Key (for DALL-E)')
                                            ->password()
                                            ->revealable()
                                            ->maxLength(500)
                                            ->placeholder('sk-...')
                                            ->helperText('Get your key at platform.openai.com/api-keys — If your LLM provider is OpenAI, the same key will be used automatically.')
                                            ->visible(fn (Forms\Get $get) => in_array($get('image_gen_provider'), ['auto', 'dalle'])),

                                        Forms\Components\Placeholder::make('image_gen_note')
                                            ->content('Image generation is disabled. The AI assistant will not be able to create custom images for your website.')
                                            ->visible(fn (Forms\Get $get) => $get('image_gen_provider') === 'none'),
                                    ])->columns(1),

                                Forms\Components\Section::make('GitHub Integration')
                                    ->description('Required for the one-click update system. Connects to the private CMS repository to check for and apply updates.')
                                    ->collapsed()
                                    ->schema([
                                        Forms\Components\TextInput::make('github_token')
                                            ->label('GitHub Personal Access Token')
                                            ->password()
                                            ->revealable()
                                            ->maxLength(500)
                                            ->placeholder('github_pat_...')
                                            ->helperText('Generate a fine-grained token at github.com/settings/tokens with read access to the CMS repository. Required for update checks on private repos.'),
                                    ])->columns(1),
                            ]),

                        Forms\Components\Tabs\Tab::make('Custom Code')
                            ->icon('heroicon-o-code-bracket')
                            ->schema([
                                Forms\Components\Textarea::make('custom_head_code')
                                    ->label('Custom Head Code')
                                    ->rows(6)
                                    ->placeholder('<!-- Paste tracking scripts, meta tags, or CSS here -->'),

                                Forms\Components\Textarea::make('custom_body_code')
                                    ->label('Custom Body Code (before </body>)')
                                    ->rows(6)
                                    ->placeholder('<!-- Paste chat widgets, analytics scripts, or JS here -->'),
                            ]),
                    ])
                    ->columnSpanFull(),
            ])
            ->statePath('data');
    }

    /**
     * Get font options including any custom fonts from embed code.
     */
    public static function getFontOptions(): array
    {
        $defaults = [
            'Inter' => 'Inter',
            'Poppins' => 'Poppins',
            'Montserrat' => 'Montserrat',
            'Roboto' => 'Roboto',
            'Open Sans' => 'Open Sans',
            'Lato' => 'Lato',
            'Playfair Display' => 'Playfair Display',
            'Merriweather' => 'Merriweather',
            'Raleway' => 'Raleway',
            'Oswald' => 'Oswald',
            'Source Sans Pro' => 'Source Sans Pro',
            'Nunito' => 'Nunito',
            'PT Sans' => 'PT Sans',
            'DM Sans' => 'DM Sans',
            'Work Sans' => 'Work Sans',
            'Outfit' => 'Outfit',
            'Space Grotesk' => 'Space Grotesk',
            'Sora' => 'Sora',
        ];

        // Parse custom fonts from embed code
        $embedCode = Setting::get('custom_font_embed', '');
        if (!empty($embedCode)) {
            // Match Google Fonts family parameter
            preg_match_all('/family=([^&"\'<>]+)/', $embedCode, $matches);
            foreach ($matches[1] ?? [] as $familyStr) {
                $families = explode('&', $familyStr);
                foreach ($families as $family) {
                    $fontName = urldecode(explode(':', $family)[0]);
                    $fontName = str_replace('+', ' ', $fontName);
                    if (!empty($fontName) && !isset($defaults[$fontName])) {
                        $defaults[$fontName] = $fontName . ' (custom)';
                    }
                }
            }

            // Match @font-face declarations
            preg_match_all('/font-family\s*:\s*["\']?([^"\';}]+)/i', $embedCode, $faceMatches);
            foreach ($faceMatches[1] ?? [] as $fontName) {
                $fontName = trim($fontName);
                if (!empty($fontName) && !isset($defaults[$fontName])) {
                    $defaults[$fontName] = $fontName . ' (custom)';
                }
            }
        }

        return $defaults;
    }

    public function save(): void
    {
        $data = $this->form->getState();

        $settingsMap = [
            'site_name' => 'general',
            'tagline' => 'general',
            'show_tagline_header' => 'general',
            'logo' => 'general',
            'favicon' => 'general',
            'home_page_id' => 'general',
            'admin_email' => 'email',
            'mail_driver' => 'email',
            'smtp_host' => 'email',
            'smtp_port' => 'email',
            'smtp_username' => 'email',
            'smtp_password' => 'email',
            'smtp_encryption' => 'email',
            'mail_from_name' => 'email',
            'mail_from_address' => 'email',
            'contact_email' => 'contact',
            'contact_phone' => 'contact',
            'contact_address' => 'contact',
            'social_facebook' => 'social',
            'social_twitter' => 'social',
            'social_instagram' => 'social',
            'social_linkedin' => 'social',
            'social_youtube' => 'social',
            'social_tiktok' => 'social',
            'google_analytics_id' => 'analytics',
            'ghl_tracking_id' => 'integrations',
            'ghl_tracking_domain' => 'integrations',
            'custom_head_code' => 'code',
            'custom_body_code' => 'code',
            'theme_primary_color' => 'theme',
            'theme_secondary_color' => 'theme',
            'theme_header_bg' => 'theme',
            'theme_footer_bg' => 'theme',
            'theme_font_heading' => 'theme',
            'theme_font_body' => 'theme',
            'custom_font_embed' => 'theme',
            'global_css' => 'theme',
            'enable_portfolio' => 'post_types',
            'enable_products' => 'post_types',
            'ai_provider' => 'ai',
            'ai_api_key' => 'ai',
            'ai_model' => 'ai',
            'ai_temperature' => 'ai',
            'ai_max_tokens' => 'ai',
            'image_gen_provider' => 'ai',
            'google_ai_api_key' => 'ai',
            'openai_image_api_key' => 'ai',
            'github_token' => 'ai',
        ];

        foreach ($settingsMap as $key => $group) {
            Setting::set($key, $data[$key] ?? '', $group);
        }

        // Sync site_name to company_name so header/footer templates can read either key
        Setting::set('company_name', $data['site_name'] ?? '', 'general');
        Setting::set('company_tagline', $data['tagline'] ?? '', 'general');

        // Flush all caches so changes take effect immediately
        Setting::flushCache();

        Notification::make()
            ->title('Settings saved successfully')
            ->success()
            ->send();
    }
}
