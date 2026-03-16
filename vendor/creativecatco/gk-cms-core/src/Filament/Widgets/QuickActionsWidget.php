<?php

namespace CreativeCatCo\GkCmsCore\Filament\Widgets;

use Filament\Widgets\Widget;

class QuickActionsWidget extends Widget
{
    protected static ?int $sort = 3;

    protected static string $view = 'cms-core::filament.widgets.quick-actions';

    protected int|string|array $columnSpan = 'full';
}
