<?php

namespace CreativeCatCo\GkCmsCore\Filament\Resources\PostResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use CreativeCatCo\GkCmsCore\Filament\Resources\PostResource;

class EditPost extends EditRecord
{
    protected static string $resource = PostResource::class;

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
