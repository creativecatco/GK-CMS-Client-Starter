<?php

namespace CreativeCatCo\GkCmsCore\Filament\Resources\PortfolioResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use CreativeCatCo\GkCmsCore\Filament\Resources\PortfolioResource;

class CreatePortfolio extends CreateRecord
{
    protected static string $resource = PortfolioResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['author_id'] = auth()->id();
        return $data;
    }
}
