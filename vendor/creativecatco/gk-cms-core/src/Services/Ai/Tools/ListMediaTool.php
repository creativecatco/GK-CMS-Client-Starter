<?php

namespace CreativeCatCo\GkCmsCore\Services\Ai\Tools;

use CreativeCatCo\GkCmsCore\Models\Media;

class ListMediaTool extends AbstractTool
{
    public function name(): string
    {
        return 'list_media';
    }

    public function description(): string
    {
        return 'List images and files in the CMS media library. Use this to find existing images that can be used on pages. Returns URLs, filenames, and folders. Filter by folder or type.';
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'folder' => [
                    'type' => 'string',
                    'description' => 'Filter by folder name (e.g., "hero", "team", "uploads"). Leave empty to list all.',
                ],
                'images_only' => [
                    'type' => 'boolean',
                    'description' => 'If true, only return image files. Default is true.',
                ],
                'limit' => [
                    'type' => 'integer',
                    'description' => 'Maximum number of results to return. Default is 50.',
                ],
            ],
            'required' => [],
        ];
    }

    public function execute(array $params): array
    {
        try {
            $query = Media::query()->orderBy('created_at', 'desc');

            if (!empty($params['folder'])) {
                $query->where('folder', $params['folder']);
            }

            $imagesOnly = $params['images_only'] ?? true;
            if ($imagesOnly) {
                $query->images();
            }

            $limit = min($params['limit'] ?? 50, 100);
            $media = $query->limit($limit)->get();

            if ($media->isEmpty()) {
                return $this->success(
                    ['media' => [], 'count' => 0],
                    'No media files found' . (!empty($params['folder']) ? " in folder '{$params['folder']}'" : '') . '.'
                );
            }

            $items = $media->map(function ($item) {
                return [
                    'id' => $item->id,
                    'filename' => $item->filename,
                    'url' => $item->url,
                    'path' => $item->path,
                    'folder' => $item->folder,
                    'mime_type' => $item->mime_type,
                    'alt_text' => $item->alt_text,
                    'size' => $item->human_size,
                ];
            })->toArray();

            // Also list available folders
            $folders = Media::query()
                ->select('folder')
                ->distinct()
                ->whereNotNull('folder')
                ->pluck('folder')
                ->toArray();

            return $this->success([
                'media' => $items,
                'count' => count($items),
                'total_in_library' => Media::count(),
                'folders' => $folders,
            ], 'Found ' . count($items) . ' media file(s). Use the URL values in templates and content.');

        } catch (\Exception $e) {
            return $this->error('Failed to list media: ' . $e->getMessage());
        }
    }
}
