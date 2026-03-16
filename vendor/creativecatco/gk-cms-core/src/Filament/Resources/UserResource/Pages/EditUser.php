<?php

namespace CreativeCatCo\GkCmsCore\Filament\Resources\UserResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use CreativeCatCo\GkCmsCore\Filament\Resources\UserResource;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

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
