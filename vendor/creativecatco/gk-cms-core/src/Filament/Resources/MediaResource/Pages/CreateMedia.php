<?php

namespace CreativeCatCo\GkCmsCore\Filament\Resources\MediaResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use CreativeCatCo\GkCmsCore\Filament\Resources\MediaResource;
use Illuminate\Support\Facades\Storage;

class CreateMedia extends CreateRecord
{
    protected static string $resource = MediaResource::class;

    /**
     * Mutate the form data before creating the record.
     * Handles the file upload → path conversion.
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // The FileUpload component stores the file and returns the path
        // We need to move it from the upload_file key to the path key
        if (!empty($data['upload_file'])) {
            $data['path'] = $data['upload_file'];

            // Auto-detect mime type and size if not set
            if (empty($data['mime_type']) || empty($data['size'])) {
                $disk = Storage::disk('public');
                if ($disk->exists($data['path'])) {
                    if (empty($data['mime_type'])) {
                        $data['mime_type'] = $disk->mimeType($data['path']);
                    }
                    if (empty($data['size'])) {
                        $data['size'] = $disk->size($data['path']);
                    }
                }
            }
        }
        unset($data['upload_file']);

        // Auto-generate filename from path if not set
        if (empty($data['filename']) && !empty($data['path'])) {
            $data['filename'] = basename($data['path']);
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
