<?php

namespace CreativeCatCo\GkCmsCore\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;
use CreativeCatCo\GkCmsCore\Models\Media;

class ImageUploadController extends Controller
{
    /**
     * Upload an image for inline editing.
     * POST /api/cms/upload-image
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'image' => 'required|image|max:10240', // 10MB max
        ]);

        $file = $request->file('image');
        $path = $file->store(
            config('cms.media_upload_path', 'cms/media') . '/fields',
            'public'
        );

        // Save to media library
        Media::create([
            'filename' => $file->getClientOriginalName(),
            'path' => $path,
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'alt_text' => $request->input('alt_text', ''),
            'folder' => 'uploads',
        ]);

        // Build the URL - handle both standard public/ and SiteGround public_html/ setups
        $url = $this->getStorageUrl($path);

        return response()->json([
            'path' => $path,
            'url' => $url,
        ]);
    }

    /**
     * Get the public URL for a storage path.
     * Handles SiteGround's public_html directory structure.
     */
    protected function getStorageUrl(string $path): string
    {
        // Check if the storage link exists in the public path
        $publicPath = public_path('storage');
        
        if (!is_link($publicPath) && !is_dir($publicPath)) {
            // Try to create the symlink
            $storagePath = storage_path('app/public');
            if (is_dir($storagePath)) {
                @symlink($storagePath, $publicPath);
            }
        }

        return asset('storage/' . $path);
    }
}
