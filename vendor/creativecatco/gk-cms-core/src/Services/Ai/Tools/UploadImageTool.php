<?php

namespace CreativeCatCo\GkCmsCore\Services\Ai\Tools;

use CreativeCatCo\GkCmsCore\Models\Media;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class UploadImageTool extends AbstractTool
{
    public function name(): string
    {
        return 'upload_image';
    }

    public function description(): string
    {
        return 'Upload an image to the CMS media library by downloading it from a URL. Returns the storage path that can be used in page fields, posts, portfolios, and products. Supports JPEG, PNG, GIF, WebP, and SVG.';
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'url' => [
                    'type' => 'string',
                    'description' => 'The public URL of the image to download and store.',
                ],
                'filename' => [
                    'type' => 'string',
                    'description' => 'Desired filename (without extension). Will be slugified. If not provided, a name will be generated from the URL.',
                ],
                'folder' => [
                    'type' => 'string',
                    'description' => 'Subfolder within the media directory (e.g., "hero", "team", "products"). Default is "uploads".',
                ],
                'alt_text' => [
                    'type' => 'string',
                    'description' => 'Alt text for accessibility. Describe what the image shows.',
                ],
            ],
            'required' => ['url'],
        ];
    }

    public function execute(array $params): array
    {
        $url = $params['url'];
        $folder = $params['folder'] ?? 'uploads';

        // Validate URL
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return $this->error("Invalid URL: '{$url}'. Please provide a valid HTTP/HTTPS URL.");
        }

        try {
            // Download the image
            $context = stream_context_create([
                'http' => [
                    'timeout' => 30,
                    'user_agent' => 'GKeys-CMS/1.0',
                ],
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                ],
            ]);

            $imageData = @file_get_contents($url, false, $context);
            if ($imageData === false) {
                return $this->error("Failed to download image from URL: '{$url}'. The URL may be inaccessible or the image may not exist.");
            }

            // Detect MIME type
            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            $mimeType = $finfo->buffer($imageData);

            $allowedTypes = [
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
                'image/gif' => 'gif',
                'image/webp' => 'webp',
                'image/svg+xml' => 'svg',
            ];

            if (!isset($allowedTypes[$mimeType])) {
                return $this->error("Unsupported image type: '{$mimeType}'. Supported: JPEG, PNG, GIF, WebP, SVG.");
            }

            $extension = $allowedTypes[$mimeType];

            // Generate filename
            if (!empty($params['filename'])) {
                $filename = Str::slug($params['filename']) . '.' . $extension;
            } else {
                $urlPath = parse_url($url, PHP_URL_PATH);
                $basename = pathinfo($urlPath, PATHINFO_FILENAME);
                $filename = Str::slug($basename ?: 'image-' . Str::random(8)) . '.' . $extension;
            }

            // Ensure unique filename
            $path = trim($folder, '/') . '/' . $filename;
            $counter = 1;
            while (Storage::disk('public')->exists($path)) {
                $path = trim($folder, '/') . '/' . pathinfo($filename, PATHINFO_FILENAME) . '-' . $counter . '.' . $extension;
                $counter++;
            }

            // Store the image
            $storagePath = config('cms.media_upload_path', 'cms/media') . '/' . $path;
            Storage::disk('public')->put($storagePath, $imageData);

            // Save to media library database
            $media = Media::create([
                'filename' => basename($path),
                'path' => $storagePath,
                'mime_type' => $mimeType,
                'size' => strlen($imageData),
                'alt_text' => $params['alt_text'] ?? '',
                'folder' => $folder,
            ]);

            $fullUrl = asset('storage/' . $storagePath);

            return $this->success([
                'path' => $storagePath,
                'url' => $fullUrl,
                'filename' => basename($path),
                'size' => strlen($imageData),
                'mime_type' => $mimeType,
                'media_id' => $media->id,
            ], "Image uploaded successfully. Use '{$fullUrl}' as the image URL in templates and content.");
        } catch (\Exception $e) {
            return $this->error("Failed to upload image: {$e->getMessage()}");
        }
    }

    public function captureRollbackData(array $params): array
    {
        return ['action' => 'upload'];
    }

    public function rollback(array $rollbackData): bool
    {
        // Image uploads are generally not rolled back to avoid data loss
        // The file remains in storage but can be manually deleted
        return false;
    }
}
