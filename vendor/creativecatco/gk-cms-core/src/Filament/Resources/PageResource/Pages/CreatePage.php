<?php

namespace CreativeCatCo\GkCmsCore\Filament\Resources\PageResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use CreativeCatCo\GkCmsCore\Filament\Resources\PageResource;
use CreativeCatCo\GkCmsCore\Models\Page;

class CreatePage extends CreateRecord
{
    protected static string $resource = PageResource::class;

    /**
     * After creating, auto-discover field definitions from the custom template.
     */
    protected function afterCreate(): void
    {
        $record = $this->record;

        if ($record->template === 'custom' && !empty($record->custom_template)) {
            $discovered = Page::discoverFieldsFromTemplate($record->custom_template);

            if (!empty($discovered)) {
                $record->field_definitions = $discovered;
                $record->saveQuietly();
            }
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('edit', ['record' => $this->record]);
    }
}
