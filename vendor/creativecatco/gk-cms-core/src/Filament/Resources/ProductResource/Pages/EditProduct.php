<?php

namespace CreativeCatCo\GkCmsCore\Filament\Resources\ProductResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use CreativeCatCo\GkCmsCore\Filament\Resources\ProductResource;

class EditProduct extends EditRecord
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
