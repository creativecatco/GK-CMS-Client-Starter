<?php

namespace CreativeCatCo\GkCmsCore\Filament\Resources\MediaResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use CreativeCatCo\GkCmsCore\Filament\Resources\MediaResource;

class CreateMedia extends CreateRecord
{
    protected static string $resource = MediaResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
