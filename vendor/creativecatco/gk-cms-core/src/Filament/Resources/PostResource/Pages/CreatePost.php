<?php

namespace CreativeCatCo\GkCmsCore\Filament\Resources\PostResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use CreativeCatCo\GkCmsCore\Filament\Resources\PostResource;

class CreatePost extends CreateRecord
{
    protected static string $resource = PostResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['author_id'] = $data['author_id'] ?? auth()->id();

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
