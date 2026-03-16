<?php

namespace CreativeCatCo\GkCmsCore\Services\Ai\Tools;

use CreativeCatCo\GkCmsCore\Models\Media;
use CreativeCatCo\GkCmsCore\Models\Setting;
use CreativeCatCo\GkCmsCore\Services\Ai\LlmProviderFactory;
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
        return 'Generate a custom AI image from a text prompt and save it to the media library. Supports hero banners, illustrations, icons, product shots, and more. The system automatically selects the best available image provider based on configured API keys. If no image API keys are configured, a free provider (Together AI FLUX) is used as fallback.';
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'prompt' => [
                    'type' => 'string',
                    'description' => 'A detailed description of the image to generate. Be specific about style, composition, lighting, colors, and subject.',
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
                    'description' => 'Aspect ratio for the image. Defaults to "16:9".',
                    'enum' => ['1:1', '16:9', '3:2', '4:3', '9:16', '2:3', '3:4', '4:5', '5:4'],
                ],
                'style' => [
                    'type' => 'string',
                    'description' => 'Image style hint. Defaults to "photorealistic".',
                    'enum' => ['photorealistic', 'illustration', 'minimal', 'abstract', 'icon'],
                ],
                'provider' => [
                    'type' => 'string',
                    'description' => 'Which AI provider to use. "auto" picks the best available. Only specify a provider if the user explicitly requests one.',
                    'enum' => ['auto', 'nano_banana', 'dalle', 'flux'],
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
        $requestedProvider = $params['provider'] ?? 'auto';

        if (empty($prompt)) {
            return $this->error('A prompt is required to generate an image.');
        }

        $filename = Str::slug($filename);
        $enhancedPrompt = $this->enhancePrompt($prompt, $style);

        // Resolve all available image API keys (independent of chat LLM provider)
        $keys = $this->resolveImageApiKeys();

        // Check if image generation is disabled
        $configuredImageProvider = Setting::get('image_gen_provider', 'auto');
        if ($configuredImageProvider === 'none') {
            return $this->error('Image generation is disabled. Enable it in Settings > AI Assistant > Image Generation.');
        }

        // Determine which provider to use
        $provider = $this->selectProvider($requestedProvider, $configuredImageProvider, $keys, $style);

        if (!$provider) {
            // No paid providers available — use free fallback
            $provider = 'flux_free';
        }

        Log::info('GenerateImageTool: using provider', ['provider' => $provider, 'style' => $style]);

        try {
            $result = $this->generateImage($provider, $enhancedPrompt, $aspectRatio, $keys);

            // If the primary provider failed, try fallbacks
            if (!$result['success']) {
                $fallbacks = $this->getFallbackProviders($provider, $keys);
                foreach ($fallbacks as $fallback) {
                    Log::info("Image generation: {$provider} failed, trying {$fallback}");
                    $result = $this->generateImage($fallback, $enhancedPrompt, $aspectRatio, $keys);
                    if ($result['success']) {
                        $provider = $fallback;
                        break;
                    }
                }
            }

            if (!$result['success']) {
                return $this->error('Image generation failed: ' . ($result['error'] ?? 'All providers failed'));
            }

            // Save the image to storage
            $imageData = $result['image_data'];
            $mimeType = $result['mime_type'] ?? 'image/png';
            $extension = $mimeType === 'image/jpeg' ? 'jpg' : 'png';
            $fullFilename = $filename . '.' . $extension;
            $storagePath = 'media/ai-generated/' . $fullFilename;

            Storage::disk('public')->makeDirectory('media/ai-generated');
            Storage::disk('public')->put($storagePath, $imageData);

            $publicUrl = '/storage/' . $storagePath;

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
            ], "Image generated successfully using {$provider} and saved as {$fullFilename}");

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
     * Resolve all available image API keys, independent of the chat LLM provider.
     * Checks dedicated image keys, main AI key, and provider-specific keys.
     */
    protected function resolveImageApiKeys(): array
    {
        $mainAiKey = Setting::get('ai_api_key', '');
        $mainProvider = Setting::get('ai_provider', 'openai');

        // Dedicated image generation keys
        $openaiImageKey = Setting::get('openai_image_api_key', '');
        $googleImageKey = Setting::get('google_ai_api_key', '');
        $togetherKey = Setting::get('together_api_key', '');

        // If no dedicated OpenAI image key, check if the main key is OpenAI
        if (empty($openaiImageKey)) {
            if ($mainProvider === 'openai' && !empty($mainAiKey)) {
                $openaiImageKey = $mainAiKey;
            } elseif (!empty($mainAiKey) && str_starts_with($mainAiKey, 'sk-') && !str_starts_with($mainAiKey, 'sk-ant-')) {
                $openaiImageKey = $mainAiKey;
            }
        }

        // If no dedicated Google image key, check if the main key is Google
        if (empty($googleImageKey)) {
            if ($mainProvider === 'google' && !empty($mainAiKey)) {
                $googleImageKey = $mainAiKey;
            } elseif (!empty($mainAiKey) && str_starts_with($mainAiKey, 'AIza')) {
                $googleImageKey = $mainAiKey;
            }
        }

        return [
            'openai' => $openaiImageKey,
            'google' => $googleImageKey,
            'together' => $togetherKey,
        ];
    }

    /**
     * Smart provider selection based on request, config, available keys, and style.
     */
    protected function selectProvider(string $requested, string $configured, array $keys, string $style): ?string
    {
        // If user explicitly requested a provider, honor it
        if ($requested !== 'auto') {
            return match ($requested) {
                'nano_banana' => !empty($keys['google']) ? 'nano_banana' : null,
                'dalle' => !empty($keys['openai']) ? 'dalle' : null,
                'flux' => !empty($keys['together']) ? 'flux' : 'flux_free',
                default => null,
            };
        }

        // If admin configured a specific provider in settings
        if ($configured !== 'auto') {
            return match ($configured) {
                'nano_banana' => !empty($keys['google']) ? 'nano_banana' : null,
                'dalle' => !empty($keys['openai']) ? 'dalle' : null,
                'flux' => !empty($keys['together']) ? 'flux' : 'flux_free',
                'together' => !empty($keys['together']) ? 'flux' : 'flux_free',
                default => null,
            };
        }

        // Auto mode: smart selection based on style and available keys
        // For photorealistic images, prefer DALL-E (best quality)
        // For illustrations/artistic, prefer Nano Banana (Gemini)
        // FLUX is a good all-rounder
        if ($style === 'photorealistic' && !empty($keys['openai'])) {
            return 'dalle';
        }
        if (in_array($style, ['illustration', 'abstract', 'icon']) && !empty($keys['google'])) {
            return 'nano_banana';
        }

        // Fall through: use whatever is available
        if (!empty($keys['openai'])) return 'dalle';
        if (!empty($keys['google'])) return 'nano_banana';
        if (!empty($keys['together'])) return 'flux';

        // No paid keys — return null to trigger free fallback
        return null;
    }

    /**
     * Get fallback providers in order of preference.
     */
    protected function getFallbackProviders(string $primary, array $keys): array
    {
        $all = [];
        if (!empty($keys['openai'])) $all[] = 'dalle';
        if (!empty($keys['google'])) $all[] = 'nano_banana';
        if (!empty($keys['together'])) $all[] = 'flux';
        $all[] = 'flux_free'; // Free fallback is always available

        return array_values(array_filter($all, fn($p) => $p !== $primary));
    }

    /**
     * Generate an image using the specified provider.
     */
    protected function generateImage(string $provider, string $prompt, string $aspectRatio, array $keys): array
    {
        return match ($provider) {
            'nano_banana' => $this->generateWithNanoBanana($prompt, $aspectRatio, $keys['google']),
            'dalle' => $this->generateWithDalle($prompt, $aspectRatio, $keys['openai']),
            'flux' => $this->generateWithFlux($prompt, $aspectRatio, $keys['together']),
            'flux_free' => $this->generateWithFluxFree($prompt, $aspectRatio),
            default => ['success' => false, 'error' => "Unknown provider: {$provider}"],
        };
    }

    /**
     * Generate image using Nano Banana (Google Gemini Image API).
     */
    protected function generateWithNanoBanana(string $prompt, string $aspectRatio, string $apiKey): array
    {
        if (empty($apiKey)) {
            return ['success' => false, 'error' => 'Google AI API key not configured'];
        }

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
     * Generate image using Together AI FLUX (paid, with API key).
     */
    protected function generateWithFlux(string $prompt, string $aspectRatio, string $apiKey): array
    {
        if (empty($apiKey)) {
            return ['success' => false, 'error' => 'Together AI API key not configured'];
        }

        $dimensions = $this->getFluxDimensions($aspectRatio);

        try {
            $response = Http::timeout(60)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $apiKey,
                    'Content-Type' => 'application/json',
                ])
                ->post('https://api.together.xyz/v1/images/generations', [
                    'model' => 'black-forest-labs/FLUX.1-schnell-Free',
                    'prompt' => $prompt,
                    'width' => $dimensions['width'],
                    'height' => $dimensions['height'],
                    'steps' => 4,
                    'n' => 1,
                    'response_format' => 'b64_json',
                ]);

            if (!$response->successful()) {
                return [
                    'success' => false,
                    'error' => 'Together AI FLUX error: ' . substr($response->body(), 0, 300),
                ];
            }

            $data = $response->json();
            $imageBase64 = $data['data'][0]['b64_json'] ?? null;

            if (empty($imageBase64)) {
                return ['success' => false, 'error' => 'Together AI FLUX returned no image data'];
            }

            return [
                'success' => true,
                'image_data' => base64_decode($imageBase64),
                'mime_type' => 'image/png',
            ];

        } catch (\Exception $e) {
            return ['success' => false, 'error' => 'Together AI FLUX exception: ' . $e->getMessage()];
        }
    }

    /**
     * Generate image using Together AI FLUX free endpoint (no API key needed).
     * This is the free fallback when no image API keys are configured.
     */
    protected function generateWithFluxFree(string $prompt, string $aspectRatio): array
    {
        $dimensions = $this->getFluxDimensions($aspectRatio);

        try {
            // Together AI offers a free FLUX.1-schnell endpoint
            // We use their free tier which requires a free API key signup
            // As an alternative, try the Pollinations.ai free API
            $encodedPrompt = urlencode($prompt);
            $width = $dimensions['width'];
            $height = $dimensions['height'];

            $response = Http::timeout(90)
                ->get("https://image.pollinations.ai/prompt/{$encodedPrompt}?width={$width}&height={$height}&nologo=true&enhance=true");

            if (!$response->successful()) {
                return [
                    'success' => false,
                    'error' => 'Free image generation failed (HTTP ' . $response->status() . ')',
                ];
            }

            $imageData = $response->body();
            if (strlen($imageData) < 1000) {
                return ['success' => false, 'error' => 'Free image generation returned invalid data'];
            }

            // Detect mime type from the response
            $mimeType = $response->header('Content-Type') ?? 'image/jpeg';
            if (str_contains($mimeType, 'png')) {
                $mimeType = 'image/png';
            } else {
                $mimeType = 'image/jpeg';
            }

            return [
                'success' => true,
                'image_data' => $imageData,
                'mime_type' => $mimeType,
            ];

        } catch (\Exception $e) {
            return ['success' => false, 'error' => 'Free image generation exception: ' . $e->getMessage()];
        }
    }

    /**
     * Map aspect ratios to FLUX-compatible pixel dimensions.
     */
    protected function getFluxDimensions(string $aspectRatio): array
    {
        return match ($aspectRatio) {
            '1:1'  => ['width' => 1024, 'height' => 1024],
            '16:9' => ['width' => 1344, 'height' => 768],
            '9:16' => ['width' => 768, 'height' => 1344],
            '3:2'  => ['width' => 1216, 'height' => 832],
            '2:3'  => ['width' => 832, 'height' => 1216],
            '4:3'  => ['width' => 1152, 'height' => 896],
            '3:4'  => ['width' => 896, 'height' => 1152],
            '4:5'  => ['width' => 896, 'height' => 1088],
            '5:4'  => ['width' => 1088, 'height' => 896],
            default => ['width' => 1344, 'height' => 768],
        };
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

        $suffix = ' High resolution, suitable for a professional website. No watermarks, no text unless specifically requested.';

        return $stylePrefix . $prompt . $suffix;
    }
}
