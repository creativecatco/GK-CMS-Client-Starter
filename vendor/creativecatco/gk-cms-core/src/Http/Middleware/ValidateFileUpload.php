<?php

namespace CreativeCatCo\GkCmsCore\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * ValidateFileUpload Middleware
 *
 * Validates uploaded files to prevent:
 * - PHP/shell script uploads disguised as images
 * - Oversized file uploads (DoS)
 * - Double extension attacks (file.php.jpg)
 * - MIME type mismatch attacks
 *
 * Applied to all routes that handle file uploads.
 */
class ValidateFileUpload
{
    /**
     * Allowed MIME types for uploads.
     */
    protected array $allowedMimeTypes = [
        // Images
        'image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml',
        'image/x-icon', 'image/vnd.microsoft.icon', 'image/ico',
        // Videos
        'video/mp4', 'video/webm', 'video/ogg', 'video/quicktime',
        // Documents
        'application/pdf',
        // Fonts
        'font/woff', 'font/woff2', 'font/ttf', 'font/otf',
        'application/font-woff', 'application/font-woff2',
    ];

    /**
     * Dangerous file extensions that should NEVER be uploaded.
     */
    protected array $blockedExtensions = [
        'php', 'phtml', 'php3', 'php4', 'php5', 'php7', 'phps', 'phar',
        'sh', 'bash', 'cgi', 'pl', 'py', 'rb', 'asp', 'aspx',
        'exe', 'bat', 'cmd', 'com', 'msi', 'dll',
        'htaccess', 'htpasswd', 'env',
        'js', 'jsx', 'ts', 'tsx', // Server-side JS
    ];

    /**
     * Maximum file size in bytes (20MB).
     */
    protected int $maxFileSize = 20 * 1024 * 1024;

    public function handle(Request $request, Closure $next): Response
    {
        if ($request->hasFile('file') || $request->hasFile('image') || $request->hasFile('logo') || $request->hasFile('video') || $request->hasFile('favicon')) {
            foreach ($request->allFiles() as $key => $files) {
                $fileArray = is_array($files) ? $files : [$files];

                foreach ($fileArray as $file) {
                    if (!$file || !$file->isValid()) {
                        continue;
                    }

                    // Check file size
                    if ($file->getSize() > $this->maxFileSize) {
                        return response()->json([
                            'message' => 'File too large. Maximum size is 20MB.',
                        ], 422);
                    }

                    // Check for blocked extensions (including double extensions)
                    $originalName = strtolower($file->getClientOriginalName());
                    foreach ($this->blockedExtensions as $ext) {
                        if (str_contains($originalName, '.' . $ext)) {
                            return response()->json([
                                'message' => "File type .{$ext} is not allowed for security reasons.",
                            ], 422);
                        }
                    }

                    // Validate MIME type
                    $mimeType = $file->getMimeType();
                    if (!in_array($mimeType, $this->allowedMimeTypes)) {
                        return response()->json([
                            'message' => "File type '{$mimeType}' is not allowed. Allowed types: images, videos, PDFs, fonts.",
                        ], 422);
                    }

                    // For images, verify the file is actually an image
                    if (str_starts_with($mimeType, 'image/') && $mimeType !== 'image/svg+xml') {
                        $imageInfo = @getimagesize($file->getRealPath());
                        if ($imageInfo === false) {
                            return response()->json([
                                'message' => 'File appears to be an image but could not be validated. It may be corrupted or not a real image.',
                            ], 422);
                        }
                    }

                    // For SVGs, check for embedded scripts
                    if ($mimeType === 'image/svg+xml') {
                        $content = file_get_contents($file->getRealPath());
                        if (preg_match('/<script|on\w+\s*=/i', $content)) {
                            return response()->json([
                                'message' => 'SVG files containing scripts are not allowed.',
                            ], 422);
                        }
                    }
                }
            }
        }

        return $next($request);
    }
}
