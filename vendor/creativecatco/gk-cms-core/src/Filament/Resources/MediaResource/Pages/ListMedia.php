<?php

namespace CreativeCatCo\GkCmsCore\Filament\Resources\MediaResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use CreativeCatCo\GkCmsCore\Filament\Resources\MediaResource;

class ListMedia extends ListRecords
{
    protected static string $resource = MediaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Upload Media'),
        ];
    }
}
