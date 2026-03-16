<?php

namespace CreativeCatCo\GkCmsCore\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;

class VideoUploadController extends Controller
{
    /**
     * Upload a video for inline editing.
     * POST /api/cms/upload-video
     *
     * Accepts: mp4, webm, ogg (max 100MB)
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'video' => 'required|file|mimes:mp4,webm,ogg|max:102400', // 100MB max
        ]);

        $path = $request->file('video')->store(
            config('cms.media_upload_path', 'cms/media') . '/videos',
            'public'
        );

        $url = $this->getStorageUrl($path);

        return response()->json([
            'path' => $path,
            'url' => $url,
            'type' => 'upload',
            'mime' => $request->file('video')->getMimeType(),
            'size' => $request->file('video')->getSize(),
        ]);
    }

    /**
     * Get the public URL for a storage path.
     */
    protected function getStorageUrl(string $path): string
    {
        $publicPath = public_path('storage');

        if (!is_link($publicPath) && !is_dir($publicPath)) {
            $storagePath = storage_path('app/public');
            if (is_dir($storagePath)) {
                @symlink($storagePath, $publicPath);
            }
        }

        return asset('storage/' . $path);
    }
}
