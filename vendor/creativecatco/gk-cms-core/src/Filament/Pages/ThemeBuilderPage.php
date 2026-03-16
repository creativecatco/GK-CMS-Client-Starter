<?php

namespace CreativeCatCo\GkCmsCore\Filament\Pages;

use Filament\Pages\Page;
use CreativeCatCo\GkCmsCore\Models\Page as CmsPage;

class ThemeBuilderPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-paint-brush';
    protected static ?string $navigationLabel = 'Theme Builder';
    protected static ?string $navigationGroup = 'Design';
    protected static ?int $navigationSort = 1;
    protected static ?string $title = 'Theme Builder';
    protected static ?string $slug = 'theme-builder';

    protected static string $view = 'cms-core::filament.pages.theme-builder';

    public function getViewData(): array
    {
        $templates = CmsPage::whereIn('page_type', ['header', 'footer', 'archive', 'single_post', 'single_portfolio'])
            ->orderBy('page_type')
            ->orderBy('title')
            ->get();

        $grouped = [
            'header' => $templates->where('page_type', 'header'),
            'footer' => $templates->where('page_type', 'footer'),
            'archive' => $templates->where('page_type', 'archive'),
            'single_post' => $templates->where('page_type', 'single_post'),
            'single_portfolio' => $templates->where('page_type', 'single_portfolio'),
        ];

        return [
            'grouped' => $grouped,
        ];
    }
}
