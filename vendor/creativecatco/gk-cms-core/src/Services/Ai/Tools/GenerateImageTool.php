<?php

namespace CreativeCatCo\GkCmsCore\Services\Ai\Tools;

use CreativeCatCo\GkCmsCore\Models\Media;
use CreativeCatCo\GkCmsCore\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class GenerateImageTool extends AbstractTool
{
    public function name(): string
    {
        return 'generate_image';
    }

    public function description(): string
    {
        return 'Generate a custom AI image from a text prompt and save it to the media library. Supports hero banners, illustrations, icons, product shots, and more. Use this to create professional visuals for the website instead of using placeholder images. The image is generated using Nano Banana (Google Gemini) or OpenAI DALL-E depending on configuration.';
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'prompt' => [
                    'type' => 'string',
                    'description' => 'A detailed description of the image to generate. Be specific about style, composition, lighting, colors, and subject. Example: "A modern, minimalist hero banner for a digital marketing agency, featuring abstract geometric shapes in blue and green gradients, with clean negative space on the right for text overlay, 16:9 aspect ratio"',
                ],
                'filename' => [
                    'type' => 'string',
                    'description' => 'The desired filename (without extension). Use descriptive names like "hero-banner", "about-team", "seo-service-illustration".',
                ],
                'alt_text' => [
                    'type' => 'string',
                    'description' => 'Alt text for the image (for SEO and accessibility).',
                ],
                'aspect_ratio' => [
                    'type' => 'string',
                    'description' => 'Aspect ratio for the image. Options: "1:1" (square), "16:9" (widescreen/hero), "3:2" (standard photo), "4:3" (classic), "9:16" (portrait/mobile). Defaults to "16:9".',
                    'enum' => ['1:1', '16:9', '3:2', '4:3', '9:16', '2:3', '3:4', '4:5', '5:4'],
                ],
                'style' => [
                    'type' => 'string',
                    'description' => 'Image style hint. Options: "photorealistic" (real-world photos), "illustration" (digital art/illustrations), "minimal" (clean/simple), "abstract" (artistic/abstract), "icon" (icon/sticker style). Defaults to "photorealistic".',
                    'enum' => ['photorealistic', 'illustration', 'minimal', 'abstract', 'icon'],
                ],
                'provider' => [
                    'type' => 'string',
                    'description' => 'Which AI provider to use. "auto" picks the best available. "nano_banana" uses Google Gemini. "dalle" uses OpenAI DALL-E. Defaults to "auto".',
                    'enum' => ['auto', 'nano_banana', 'dalle'],
                ],
            ],
            'required' => ['prompt', 'filename'],
        ];
    }

    public function execute(array $params): array
    {
        $prompt = $params['prompt'] ?? '';
        $filename = $params['filename'] ?? 'generated-image';
        $altText = $params['alt_text'] ?? $prompt;
        $aspectRatio = $params['aspect_ratio'] ?? '16:9';
        $style = $params['style'] ?? 'photorealistic';
        $provider = $params['provider'] ?? 'auto';

        if (empty($prompt)) {
            return $this->error('A prompt is required to generate an image.');
        }

        // Clean filename
        $filename = Str::slug($filename);

        // Enhance the prompt based on style
        $enhancedPrompt = $this->enhancePrompt($prompt, $style);

        // Determine which provider to use
        $configuredImageProvider = Setting::get('image_gen_provider', 'auto');
        $aiProvider = Setting::get('ai_provider', 'openai');

        // Resolve Google AI API key: dedicated key > main AI key (if Google provider)
        $googleApiKey = Setting::get('google_ai_api_key', '');
        if (empty($googleApiKey) && $aiProvider === 'google') {
            $googleApiKey = Setting::get('ai_api_key', '');
        }

        // Resolve OpenAI API key: dedicated image key > main AI key (if OpenAI provider)
        $openaiApiKey = Setting::get('openai_image_api_key', '');
        if (empty($openaiApiKey) && $aiProvider === 'openai') {
            $openaiApiKey = Setting::get('ai_api_key', '');
        }

        // If image generation is disabled in settings, return error
        if ($configuredImageProvider === 'none') {
            return $this->error('Image generation is disabled. Enable it in Settings > AI Assistant > Image Generation.');
        }

        // Use the settings-configured provider if the tool request is 'auto'
        if ($provider === 'auto') {
            if ($configuredImageProvider !== 'auto') {
                $provider = $configuredImageProvider;
            } else {
                // Auto-detect: prefer Nano Banana if Google key is available
                if (!empty($googleApiKey)) {
                    $provider = 'nano_banana';
                } elseif (!empty($openaiApiKey)) {
                    $provider = 'dalle';
                } else {
                    return $this->error('No image generation API key configured. Please add a Google AI API key (for Nano Banana) or OpenAI API key (for DALL-E) in Settings > AI Assistant > Image Generation.');
                }
            }
        }

        try {
            if ($provider === 'nano_banana') {
                $result = $this->generateWithNanoBanana($enhancedPrompt, $aspectRatio, $googleApiKey);
            } else {
                $result = $this->generateWithDalle($enhancedPrompt, $aspectRatio, $openaiApiKey);
            }

            if (!$result['success']) {
                // Fallback to the other provider
                if ($provider === 'nano_banana' && !empty($openaiApiKey)) {
                    Log::info('Nano Banana failed, falling back to DALL-E');
                    $result = $this->generateWithDalle($enhancedPrompt, $aspectRatio, $openaiApiKey);
                } elseif ($provider === 'dalle' && !empty($googleApiKey)) {
                    Log::info('DALL-E failed, falling back to Nano Banana');
                    $result = $this->generateWithNanoBanana($enhancedPrompt, $aspectRatio, $googleApiKey);
                }

                if (!$result['success']) {
                    return $this->error('Image generation failed: ' . ($result['error'] ?? 'Unknown error'));
                }
            }

            // Save the image to storage
            $imageData = $result['image_data'];
            $mimeType = $result['mime_type'] ?? 'image/png';
            $extension = $mimeType === 'image/jpeg' ? 'jpg' : 'png';
            $fullFilename = $filename . '.' . $extension;
            $storagePath = 'media/ai-generated/' . $fullFilename;

            // Ensure directory exists
            Storage::disk('public')->makeDirectory('media/ai-generated');
            Storage::disk('public')->put($storagePath, $imageData);

            $publicUrl = '/storage/' . $storagePath;

            // Save to Media database
            try {
                Media::create([
                    'filename' => $fullFilename,
                    'path' => $storagePath,
                    'mime_type' => $mimeType,
                    'size' => strlen($imageData),
                    'alt_text' => mb_substr($altText, 0, 255),
                    'disk' => 'public',
                ]);
            } catch (\Exception $e) {
                Log::warning('Failed to save generated image to Media table', ['error' => $e->getMessage()]);
            }

            return $this->success([
                'url' => $publicUrl,
                'filename' => $fullFilename,
                'path' => $storagePath,
                'provider' => $provider,
                'alt_text' => $altText,
                'aspect_ratio' => $aspectRatio,
            ], "Image generated successfully and saved to gallery as {$fullFilename}");

        } catch (\Exception $e) {
            Log::error('GenerateImageTool error', [
                'prompt' => $prompt,
                'provider' => $provider,
                'error' => $e->getMessage(),
            ]);
            return $this->error('Image generation failed: ' . $e->getMessage());
        }
    }

    /**
     * Generate image using Nano Banana (Google Gemini Image API).
     */
    protected function generateWithNanoBanana(string $prompt, string $aspectRatio, string $apiKey): array
    {
        if (empty($apiKey)) {
            return ['success' => false, 'error' => 'Google AI API key not configured'];
        }

        // Use Gemini 2.5 Flash Image (widely available) with fallback to 3.1 Flash
        $models = [
            'gemini-2.5-flash-preview-image-generation',
            'gemini-2.0-flash-exp',
        ];

        foreach ($models as $model) {
            try {
                $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";

                $payload = [
                    'contents' => [
                        [
                            'parts' => [
                                ['text' => $prompt],
                            ],
                        ],
                    ],
                    'generationConfig' => [
                        'responseModalities' => ['TEXT', 'IMAGE'],
                        'imageConfig' => [
                            'aspectRatio' => $aspectRatio,
                        ],
                    ],
                ];

                $response = Http::timeout(60)
                    ->withHeaders(['Content-Type' => 'application/json'])
                    ->post($url, $payload);

                if (!$response->successful()) {
                    Log::warning("Nano Banana model {$model} failed", [
                        'status' => $response->status(),
                        'body' => substr($response->body(), 0, 500),
                    ]);
                    continue;
                }

                $data = $response->json();
                $candidates = $data['candidates'] ?? [];

                if (empty($candidates)) {
                    continue;
                }

                // Find the image part in the response
                $parts = $candidates[0]['content']['parts'] ?? [];
                foreach ($parts as $part) {
                    if (isset($part['inlineData'])) {
                        $imageBase64 = $part['inlineData']['data'];
                        $mimeType = $part['inlineData']['mimeType'] ?? 'image/png';

                        return [
                            'success' => true,
                            'image_data' => base64_decode($imageBase64),
                            'mime_type' => $mimeType,
                        ];
                    }
                }

                Log::warning("Nano Banana model {$model} returned no image data");
                continue;

            } catch (\Exception $e) {
                Log::warning("Nano Banana model {$model} exception", ['error' => $e->getMessage()]);
                continue;
            }
        }

        return ['success' => false, 'error' => 'All Nano Banana models failed to generate an image'];
    }

    /**
     * Generate image using OpenAI DALL-E.
     */
    protected function generateWithDalle(string $prompt, string $aspectRatio, string $apiKey): array
    {
        if (empty($apiKey)) {
            return ['success' => false, 'error' => 'OpenAI API key not configured'];
        }

        // Map aspect ratios to DALL-E supported sizes
        $sizeMap = [
            '1:1' => '1024x1024',
            '16:9' => '1792x1024',
            '9:16' => '1024x1792',
            '3:2' => '1792x1024',
            '2:3' => '1024x1792',
            '4:3' => '1792x1024',
            '3:4' => '1024x1792',
            '4:5' => '1024x1792',
            '5:4' => '1792x1024',
        ];

        $size = $sizeMap[$aspectRatio] ?? '1792x1024';

        try {
            $response = Http::timeout(60)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $apiKey,
                    'Content-Type' => 'application/json',
                ])
                ->post('https://api.openai.com/v1/images/generations', [
                    'model' => 'dall-e-3',
                    'prompt' => $prompt,
                    'n' => 1,
                    'size' => $size,
                    'response_format' => 'b64_json',
                    'quality' => 'standard',
                ]);

            if (!$response->successful()) {
                return [
                    'success' => false,
                    'error' => 'DALL-E API error: ' . substr($response->body(), 0, 300),
                ];
            }

            $data = $response->json();
            $imageBase64 = $data['data'][0]['b64_json'] ?? null;

            if (empty($imageBase64)) {
                return ['success' => false, 'error' => 'DALL-E returned no image data'];
            }

            return [
                'success' => true,
                'image_data' => base64_decode($imageBase64),
                'mime_type' => 'image/png',
            ];

        } catch (\Exception $e) {
            return ['success' => false, 'error' => 'DALL-E exception: ' . $e->getMessage()];
        }
    }

    /**
     * Enhance the prompt based on the desired style.
     */
    protected function enhancePrompt(string $prompt, string $style): string
    {
        $stylePrefix = match ($style) {
            'photorealistic' => 'A high-quality, photorealistic image. ',
            'illustration' => 'A modern digital illustration with clean lines and vibrant colors. ',
            'minimal' => 'A clean, minimalist design with ample negative space and subtle colors. ',
            'abstract' => 'An artistic, abstract composition with bold shapes and creative color usage. ',
            'icon' => 'A clean, simple icon or sticker design on a white or transparent background. ',
            default => '',
        };

        // Add web-specific quality hints
        $suffix = ' High resolution, suitable for a professional website. No watermarks, no text unless specifically requested.';

        return $stylePrefix . $prompt . $suffix;
    }
}
