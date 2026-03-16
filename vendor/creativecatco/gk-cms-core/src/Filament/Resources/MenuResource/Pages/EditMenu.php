<?php

namespace CreativeCatCo\GkCmsCore\Filament\Resources\MenuResource\Pages;

use CreativeCatCo\GkCmsCore\Filament\Resources\MenuResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMenu extends EditRecord
{
    protected static string $resource = MenuResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
