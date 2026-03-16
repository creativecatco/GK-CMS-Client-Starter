<?php

namespace CreativeCatCo\GkCmsCore\Filament\Resources\PortfolioResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use CreativeCatCo\GkCmsCore\Filament\Resources\PortfolioResource;

class ListPortfolios extends ListRecords
{
    protected static string $resource = PortfolioResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
