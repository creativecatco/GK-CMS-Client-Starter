<?php

namespace CreativeCatCo\GkCmsCore\Filament\Resources\ProductResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use CreativeCatCo\GkCmsCore\Filament\Resources\ProductResource;

class CreateProduct extends CreateRecord
{
    protected static string $resource = ProductResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['author_id'] = auth()->id();
        return $data;
    }
}
