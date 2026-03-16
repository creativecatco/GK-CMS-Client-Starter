<?php

namespace CreativeCatCo\GkCmsCore\Filament\Resources\MenuResource\Pages;

use CreativeCatCo\GkCmsCore\Filament\Resources\MenuResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMenus extends ListRecords
{
    protected static string $resource = MenuResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
