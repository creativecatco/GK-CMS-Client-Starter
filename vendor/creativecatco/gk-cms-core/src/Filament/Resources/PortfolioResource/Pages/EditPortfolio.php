<?php

namespace CreativeCatCo\GkCmsCore\Filament\Resources\PortfolioResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use CreativeCatCo\GkCmsCore\Filament\Resources\PortfolioResource;

class EditPortfolio extends EditRecord
{
    protected static string $resource = PortfolioResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
