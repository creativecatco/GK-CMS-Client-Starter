<?php

namespace CreativeCatCo\GkCmsCore\Filament\Resources\MediaResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use CreativeCatCo\GkCmsCore\Filament\Resources\MediaResource;

class EditMedia extends EditRecord
{
    protected static string $resource = MediaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
