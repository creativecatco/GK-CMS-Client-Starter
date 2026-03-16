<?php

namespace CreativeCatCo\GkCmsCore;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Filament\Support\Colors\Color;

/**
 * GKeys CMS Core Filament Plugin
 *
 * This plugin auto-registers all package pages, resources, widgets,
 * navigation groups, brand assets, and theme CSS into the host
 * application's Filament panel.
 *
 * The host app only needs:
 *   ->plugin(CmsCorePlugin::make())
 *
 * When we add new pages/resources to the package, they are automatically
 * picked up on the next update without any host app changes.
 */
class CmsCorePlugin implements Plugin
{
    public static function make(): static
    {
        return new static();
    }

    public function getId(): string
    {
        return 'gk-cms-core';
    }

    public function register(Panel $panel): void
    {
        // Auto-discover all resources from the package
        $panel->discoverResources(
            in: __DIR__ . '/Filament/Resources',
            for: 'CreativeCatCo\\GkCmsCore\\Filament\\Resources'
        );

        // Auto-discover all pages from the package
        $panel->discoverPages(
            in: __DIR__ . '/Filament/Pages',
            for: 'CreativeCatCo\\GkCmsCore\\Filament\\Pages'
        );

        // Register widgets
        $panel->widgets([
            \CreativeCatCo\GkCmsCore\Filament\Widgets\StatsOverviewWidget::class,
            \CreativeCatCo\GkCmsCore\Filament\Widgets\RecentActivityWidget::class,
            \CreativeCatCo\GkCmsCore\Filament\Widgets\QuickActionsWidget::class,
        ]);

        // Register navigation groups
        $panel->navigationGroups([
            'Content',
            'Design',
            'System',
            'Administration',
        ]);

        // Brand settings
        $panel
            ->brandName('GKeys CMS')
            ->brandLogo('/vendor/cms-core/brand/gkeys-logo-dark.webp')
            ->darkModeBrandLogo('/vendor/cms-core/brand/gkeys-logo-dark.webp')
            ->brandLogoHeight('2.5rem')
            ->favicon(asset('vendor/cms-core/img/gkeys-icon-favicon.png'))
            ->darkMode(true, true)
            ->sidebarCollapsibleOnDesktop()
            ->colors([
                'primary' => Color::hex('#293726'),
                'danger'  => Color::hex('#3f1111'),
                'gray'    => Color::hex('#15171e'),
                'info'    => Color::hex('#3f524e'),
                'success' => Color::hex('#d2eb00'),
                'warning' => Color::hex('#43302e'),
            ]);

        // Inject the GKeys admin theme CSS
        $panel->renderHook(
            'panels::styles.after',
            fn () => $this->getThemeCss()
        );
    }

    public function boot(Panel $panel): void
    {
        // Nothing needed at boot time
    }

    /**
     * Get the GKeys admin theme CSS.
     */
    public function getThemeCss(): string
    {
        $cssPath = __DIR__ . '/../resources/assets/css/admin-theme.css';

        if (file_exists($cssPath)) {
            return '<style>' . file_get_contents($cssPath) . '</style>';
        }

        // Fallback: load from CmsPanelProvider
        $provider = new CmsPanelProvider(app());
        return $provider->getThemeCss();
    }
}
