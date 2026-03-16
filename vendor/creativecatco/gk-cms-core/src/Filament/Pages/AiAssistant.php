<?php

namespace CreativeCatCo\GkCmsCore\Filament\Pages;

use CreativeCatCo\GkCmsCore\Services\Ai\LlmProviderFactory;
use CreativeCatCo\GkCmsCore\Models\Setting;
use Filament\Pages\Page;

class AiAssistant extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-sparkles';
    protected static ?string $navigationLabel = 'AI Assistant';
    protected static ?string $title = 'AI Website Builder';
    protected static ?string $slug = 'ai-assistant';
    protected static ?string $navigationGroup = 'Content';
    protected static ?int $navigationSort = -1;

    // Use the package's view
    protected static string $view = 'cms-core::filament.pages.ai-assistant';

    /**
     * Disable the default Filament page header to maximize space.
     */
    protected function getHeaderActions(): array
    {
        return [];
    }

    /**
     * Get the data to pass to the view.
     */
    protected function getViewData(): array
    {
        return [
            'isConfigured' => LlmProviderFactory::isConfigured(),
            'provider' => Setting::get('ai_provider', 'Not set'),
            'model' => Setting::get('ai_model', 'Not set'),
            'configuredProviders' => LlmProviderFactory::getConfiguredProviders(),
        ];
    }
}
