<?php

namespace CreativeCatCo\GkCmsCore\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use CreativeCatCo\GkCmsCore\Models\Page;
use CreativeCatCo\GkCmsCore\Models\Post;
use CreativeCatCo\GkCmsCore\Models\Media;

class StatsOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        return [
            Stat::make('Total Pages', Page::count())
                ->description('Published: ' . Page::published()->count())
                ->descriptionIcon('heroicon-m-document-text')
                ->color('primary'),

            Stat::make('Total Posts', Post::count())
                ->description('Published: ' . Post::published()->count())
                ->descriptionIcon('heroicon-m-newspaper')
                ->color('info'),

            Stat::make('Total Media', Media::count())
                ->description('Images: ' . Media::images()->count())
                ->descriptionIcon('heroicon-m-photo')
                ->color('success'),
        ];
    }
}
