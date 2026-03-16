<?php

namespace CreativeCatCo\GkCmsCore\Filament\Pages;

use Filament\Pages\Page;

class PluginsPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-puzzle-piece';
    protected static ?string $navigationLabel = 'Plugins';
    protected static ?string $title = 'Plugins';
    protected static ?string $slug = 'plugins';
    protected static ?string $navigationGroup = 'System';
    protected static ?int $navigationSort = 10;

    protected static string $view = 'cms-core::filament.pages.plugins';
}
