<?php

namespace CreativeCatCo\GkCmsCore\Filament\Resources\PageResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use CreativeCatCo\GkCmsCore\Filament\Resources\PageResource;
use CreativeCatCo\GkCmsCore\Models\Page;

class CreatePage extends CreateRecord
{
    protected static string $resource = PageResource::class;

    /**
     * Before creating, merge display_on_pages / display_on_types into the display_on column.
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $scope = $data['display_scope'] ?? 'all';

        if ($scope === 'specific_pages' && isset($data['display_on_pages'])) {
            $data['display_on'] = array_map('intval', $data['display_on_pages']);
        } elseif ($scope === 'specific_types' && isset($data['display_on_types'])) {
            $data['display_on'] = $data['display_on_types'];
        } else {
            $data['display_on'] = null;
        }

        unset($data['display_on_pages'], $data['display_on_types']);

        return $data;
    }

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
