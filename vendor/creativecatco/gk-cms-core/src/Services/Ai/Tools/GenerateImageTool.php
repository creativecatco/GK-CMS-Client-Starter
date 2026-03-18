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
        return 'Generate a custom AI image from a text prompt and save it to the media library. Supports hero banners, illustrations, icons, product shots, and more. The system automatically selects the best available image provider based on configured API keys. If no image API keys are configured, a free provider (Pollinations) is used as fallback.';
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
                    'enum' => ['auto', 'nano_banana', 'dalle', 'gpt_image', 'flux', 'pollinations'],
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
            // No paid providers available — use free Pollinations fallback
            $provider = 'pollinations_free';
        }

        Log::info('GenerateImageTool: using provider', [
            'provider' => $provider,
            'style' => $style,
            'has_openai_key' => !empty($keys['openai']),
            'has_google_key' => !empty($keys['google']),
            'has_together_key' => !empty($keys['together']),
            'has_pollinations_key' => !empty($keys['pollinations']),
        ]);

        try {
            $result = $this->generateImage($provider, $enhancedPrompt, $aspectRatio, $keys);

            // If the primary provider failed, try fallbacks
            if (!$result['success']) {
                Log::warning("Image generation: {$provider} failed", ['error' => $result['error'] ?? 'Unknown']);
                $fallbacks = $this->getFallbackProviders($provider, $keys);
                foreach ($fallbacks as $fallback) {
                    Log::info("Image generation: trying fallback {$fallback}");
                    $result = $this->generateImage($fallback, $enhancedPrompt, $aspectRatio, $keys);
                    if ($result['success']) {
                        $provider = $fallback;
                        break;
                    }
                    Log::warning("Image generation: fallback {$fallback} also failed", ['error' => $result['error'] ?? 'Unknown']);
                }
            }

            if (!$result['success']) {
                return $this->error('Image generation failed: ' . ($result['error'] ?? 'All providers failed. Please check your API keys in Settings > AI Assistant.'));
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

            // Determine citation/attribution
            $citation = $this->getProviderCitation($provider);

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
                'citation' => $citation,
            ], "Image generated successfully using {$provider} and saved as {$fullFilename}");

        } catch (\Exception $e) {
            Log::error('GenerateImageTool error', [
                'prompt' => $prompt,
                'provider' => $provider,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
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
        $pollinationsKey = Setting::get('pollinations_api_key', '');

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
            'pollinations' => $pollinationsKey,
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
                'gpt_image' => !empty($keys['openai']) ? 'gpt_image' : null,
                'flux' => !empty($keys['together']) ? 'flux' : null,
                'pollinations' => !empty($keys['pollinations']) ? 'pollinations' : 'pollinations_free',
                default => null,
            };
        }

        // If admin configured a specific provider in settings
        if ($configured !== 'auto') {
            return match ($configured) {
                'nano_banana' => !empty($keys['google']) ? 'nano_banana' : null,
                'dalle' => !empty($keys['openai']) ? 'dalle' : null,
                'gpt_image' => !empty($keys['openai']) ? 'gpt_image' : null,
                'flux' => !empty($keys['together']) ? 'flux' : null,
                'together' => !empty($keys['together']) ? 'flux' : null,
                'pollinations' => !empty($keys['pollinations']) ? 'pollinations' : 'pollinations_free',
                default => null,
            };
        }

        // Auto mode: smart selection based on style and available keys
        // For photorealistic images, prefer DALL-E / GPT Image (best quality)
        // For illustrations/artistic, prefer Nano Banana (Gemini)
        // FLUX is a good all-rounder
        if ($style === 'photorealistic' && !empty($keys['openai'])) {
            return 'gpt_image';
        }
        if (in_array($style, ['illustration', 'abstract', 'icon']) && !empty($keys['google'])) {
            return 'nano_banana';
        }

        // Fall through: use whatever is available
        if (!empty($keys['openai'])) return 'gpt_image';
        if (!empty($keys['google'])) return 'nano_banana';
        if (!empty($keys['together'])) return 'flux';
        if (!empty($keys['pollinations'])) return 'pollinations';

        // No paid keys — return null to trigger free fallback
        return null;
    }

    /**
     * Get fallback providers in order of preference.
     */
    protected function getFallbackProviders(string $primary, array $keys): array
    {
        $all = [];
        if (!empty($keys['openai'])) $all[] = 'gpt_image';
        if (!empty($keys['openai'])) $all[] = 'dalle';
        if (!empty($keys['google'])) $all[] = 'nano_banana';
        if (!empty($keys['together'])) $all[] = 'flux';
        if (!empty($keys['pollinations'])) $all[] = 'pollinations';
        $all[] = 'pollinations_free'; // Free fallback is always available

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
            'gpt_image' => $this->generateWithGptImage($prompt, $aspectRatio, $keys['openai']),
            'flux' => $this->generateWithFlux($prompt, $aspectRatio, $keys['together']),
            'pollinations' => $this->generateWithPollinations($prompt, $aspectRatio, $keys['pollinations']),
            'pollinations_free' => $this->generateWithPollinationsFree($prompt, $aspectRatio),
            default => ['success' => false, 'error' => "Unknown provider: {$provider}"],
        };
    }

    /**
     * Generate image using Nano Banana (Google Gemini Image API).
     * Uses the correct stable model name: gemini-2.5-flash-image
     */
    protected function generateWithNanoBanana(string $prompt, string $aspectRatio, string $apiKey): array
    {
        if (empty($apiKey)) {
            return ['success' => false, 'error' => 'Google AI API key not configured'];
        }

        // Model names in order of preference (stable first, then preview/newer)
        $models = [
            'gemini-2.5-flash-image',              // Stable Nano Banana
            'gemini-3.1-flash-image-preview',       // Nano Banana 2 (preview)
        ];

        foreach ($models as $model) {
            try {
                $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";

                // Convert aspect ratio format for Gemini (uses "16:9" format directly)
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
                    ],
                ];

                // Only add imageConfig if aspect ratio is not default
                if ($aspectRatio !== '1:1') {
                    $payload['generationConfig']['imageConfig'] = [
                        'aspectRatio' => str_replace(':', ':', $aspectRatio),
                    ];
                }

                Log::info("Nano Banana: trying model {$model}");

                $response = Http::timeout(90)
                    ->withHeaders(['Content-Type' => 'application/json'])
                    ->post($url, $payload);

                if (!$response->successful()) {
                    $body = substr($response->body(), 0, 500);
                    Log::warning("Nano Banana model {$model} failed", [
                        'status' => $response->status(),
                        'body' => $body,
                    ]);

                    // If it's a model not found error, try next model
                    if ($response->status() === 404 || str_contains($body, 'not found')) {
                        continue;
                    }

                    // If it's an auth error, no point trying other models
                    if ($response->status() === 400 && str_contains($body, 'API_KEY_INVALID')) {
                        return ['success' => false, 'error' => 'Google AI API key is invalid. Please check your key in Settings.'];
                    }
                    if ($response->status() === 403) {
                        return ['success' => false, 'error' => 'Google AI API key does not have permission for image generation.'];
                    }

                    continue;
                }

                $data = $response->json();
                $candidates = $data['candidates'] ?? [];

                if (empty($candidates)) {
                    Log::warning("Nano Banana model {$model} returned no candidates");
                    continue;
                }

                $parts = $candidates[0]['content']['parts'] ?? [];
                foreach ($parts as $part) {
                    if (isset($part['inlineData'])) {
                        $imageBase64 = $part['inlineData']['data'];
                        $mimeType = $part['inlineData']['mimeType'] ?? 'image/png';

                        Log::info("Nano Banana: successfully generated image with {$model}");

                        return [
                            'success' => true,
                            'image_data' => base64_decode($imageBase64),
                            'mime_type' => $mimeType,
                        ];
                    }
                }

                Log::warning("Nano Banana model {$model} returned no image data in parts");
                continue;

            } catch (\Exception $e) {
                Log::warning("Nano Banana model {$model} exception", ['error' => $e->getMessage()]);
                continue;
            }
        }

        return ['success' => false, 'error' => 'All Nano Banana models failed to generate an image'];
    }

    /**
     * Generate image using OpenAI DALL-E 3.
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
            $response = Http::timeout(90)
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
                $body = $response->body();
                if (str_contains($body, 'invalid_api_key') || str_contains($body, 'Incorrect API key')) {
                    return ['success' => false, 'error' => 'OpenAI API key is invalid. Please check your key in Settings.'];
                }
                if (str_contains($body, 'billing') || str_contains($body, 'quota')) {
                    return ['success' => false, 'error' => 'OpenAI billing issue. Please check your OpenAI account has credits.'];
                }
                return [
                    'success' => false,
                    'error' => 'DALL-E API error (HTTP ' . $response->status() . '): ' . substr($body, 0, 300),
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
     * Generate image using OpenAI GPT Image 1 (newer model, successor to DALL-E).
     */
    protected function generateWithGptImage(string $prompt, string $aspectRatio, string $apiKey): array
    {
        if (empty($apiKey)) {
            return ['success' => false, 'error' => 'OpenAI API key not configured'];
        }

        $sizeMap = [
            '1:1' => '1024x1024',
            '16:9' => '1536x1024',
            '9:16' => '1024x1536',
            '3:2' => '1536x1024',
            '2:3' => '1024x1536',
            '4:3' => '1536x1024',
            '3:4' => '1024x1536',
            '4:5' => '1024x1536',
            '5:4' => '1536x1024',
        ];

        $size = $sizeMap[$aspectRatio] ?? '1536x1024';

        try {
            // Try gpt-image-1 first, then fall back to gpt-image-1-mini
            $models = ['gpt-image-1', 'gpt-image-1-mini'];

            foreach ($models as $model) {
                $response = Http::timeout(90)
                    ->withHeaders([
                        'Authorization' => 'Bearer ' . $apiKey,
                        'Content-Type' => 'application/json',
                    ])
                    ->post('https://api.openai.com/v1/images/generations', [
                        'model' => $model,
                        'prompt' => $prompt,
                        'n' => 1,
                        'size' => $size,
                        'quality' => 'medium',
                    ]);

                if ($response->successful()) {
                    $data = $response->json();

                    // GPT Image models may return URL or b64_json
                    $imageUrl = $data['data'][0]['url'] ?? null;
                    $imageBase64 = $data['data'][0]['b64_json'] ?? null;

                    if (!empty($imageBase64)) {
                        return [
                            'success' => true,
                            'image_data' => base64_decode($imageBase64),
                            'mime_type' => 'image/png',
                        ];
                    }

                    if (!empty($imageUrl)) {
                        // Download the image from the URL
                        $imageResponse = Http::timeout(30)->get($imageUrl);
                        if ($imageResponse->successful() && strlen($imageResponse->body()) > 1000) {
                            return [
                                'success' => true,
                                'image_data' => $imageResponse->body(),
                                'mime_type' => $imageResponse->header('Content-Type') ?? 'image/png',
                            ];
                        }
                    }

                    Log::warning("GPT Image model {$model} returned no usable image data");
                    continue;
                }

                $body = $response->body();
                // If model not found, try next
                if ($response->status() === 404 || str_contains($body, 'model_not_found')) {
                    Log::info("GPT Image model {$model} not available, trying next");
                    continue;
                }

                // Auth/billing errors - don't try other models
                if (str_contains($body, 'invalid_api_key')) {
                    return ['success' => false, 'error' => 'OpenAI API key is invalid.'];
                }
                if (str_contains($body, 'billing') || str_contains($body, 'quota')) {
                    return ['success' => false, 'error' => 'OpenAI billing issue.'];
                }

                Log::warning("GPT Image model {$model} failed", ['status' => $response->status(), 'body' => substr($body, 0, 300)]);
            }

            return ['success' => false, 'error' => 'GPT Image generation failed with all model variants'];

        } catch (\Exception $e) {
            return ['success' => false, 'error' => 'GPT Image exception: ' . $e->getMessage()];
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
            // Try the free model first, then the paid one
            $models = [
                'black-forest-labs/FLUX.1-schnell-Free',
                'black-forest-labs/FLUX.1-schnell',
            ];

            foreach ($models as $model) {
                $response = Http::timeout(90)
                    ->withHeaders([
                        'Authorization' => 'Bearer ' . $apiKey,
                        'Content-Type' => 'application/json',
                    ])
                    ->post('https://api.together.xyz/v1/images/generations', [
                        'model' => $model,
                        'prompt' => $prompt,
                        'width' => $dimensions['width'],
                        'height' => $dimensions['height'],
                        'steps' => 4,
                        'n' => 1,
                        'response_format' => 'b64_json',
                    ]);

                if ($response->successful()) {
                    $data = $response->json();
                    $imageBase64 = $data['data'][0]['b64_json'] ?? null;

                    if (!empty($imageBase64)) {
                        return [
                            'success' => true,
                            'image_data' => base64_decode($imageBase64),
                            'mime_type' => 'image/png',
                        ];
                    }
                }

                $body = $response->body();
                if (str_contains($body, 'model_not_found') || $response->status() === 404) {
                    continue;
                }

                Log::warning("Together AI FLUX model {$model} failed", ['status' => $response->status(), 'body' => substr($body, 0, 300)]);
            }

            return ['success' => false, 'error' => 'Together AI FLUX generation failed'];

        } catch (\Exception $e) {
            return ['success' => false, 'error' => 'Together AI FLUX exception: ' . $e->getMessage()];
        }
    }

    /**
     * Generate image using Pollinations API (with API key).
     * Uses the new gen.pollinations.ai endpoint.
     */
    protected function generateWithPollinations(string $prompt, string $aspectRatio, string $apiKey): array
    {
        if (empty($apiKey)) {
            return ['success' => false, 'error' => 'Pollinations API key not configured'];
        }

        $dimensions = $this->getFluxDimensions($aspectRatio);

        try {
            // Use the OpenAI-compatible endpoint
            $response = Http::timeout(90)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $apiKey,
                    'Content-Type' => 'application/json',
                ])
                ->post('https://gen.pollinations.ai/v1/images/generations', [
                    'model' => 'flux',
                    'prompt' => $prompt,
                    'size' => $dimensions['width'] . 'x' . $dimensions['height'],
                    'response_format' => 'b64_json',
                ]);

            if (!$response->successful()) {
                return [
                    'success' => false,
                    'error' => 'Pollinations API error (HTTP ' . $response->status() . '): ' . substr($response->body(), 0, 300),
                ];
            }

            $data = $response->json();
            $imageBase64 = $data['data'][0]['b64_json'] ?? null;
            $imageUrl = $data['data'][0]['url'] ?? null;

            if (!empty($imageBase64)) {
                return [
                    'success' => true,
                    'image_data' => base64_decode($imageBase64),
                    'mime_type' => 'image/png',
                ];
            }

            if (!empty($imageUrl)) {
                $imageResponse = Http::timeout(30)->get($imageUrl);
                if ($imageResponse->successful() && strlen($imageResponse->body()) > 1000) {
                    return [
                        'success' => true,
                        'image_data' => $imageResponse->body(),
                        'mime_type' => $imageResponse->header('Content-Type') ?? 'image/jpeg',
                    ];
                }
            }

            return ['success' => false, 'error' => 'Pollinations returned no image data'];

        } catch (\Exception $e) {
            return ['success' => false, 'error' => 'Pollinations exception: ' . $e->getMessage()];
        }
    }

    /**
     * Generate image using Pollinations free endpoint (no API key needed).
     * Uses the legacy image.pollinations.ai endpoint with minimal parameters.
     * NOTE: This endpoint has rate limits and may include a watermark.
     */
    protected function generateWithPollinationsFree(string $prompt, string $aspectRatio): array
    {
        try {
            // The free endpoint works best with just a prompt and no extra parameters.
            // Adding width/height/enhance causes 500 errors on the free tier.
            $encodedPrompt = urlencode($prompt);

            // Use the simple GET endpoint - no width/height/enhance params
            // The free endpoint returns ~1024x1024 images by default
            $url = "https://image.pollinations.ai/prompt/{$encodedPrompt}?nologo=true&seed=" . rand(1, 999999);

            Log::info('Pollinations free: requesting image', ['url' => substr($url, 0, 200)]);

            $response = Http::timeout(120)
                ->withOptions(['allow_redirects' => true])
                ->get($url);

            if (!$response->successful()) {
                $statusCode = $response->status();

                // Rate limiting
                if ($statusCode === 429) {
                    Log::warning('Pollinations free: rate limited, waiting and retrying...');
                    sleep(5);

                    // Retry once
                    $response = Http::timeout(120)
                        ->withOptions(['allow_redirects' => true])
                        ->get($url);

                    if (!$response->successful()) {
                        return [
                            'success' => false,
                            'error' => 'Pollinations free: rate limited (HTTP 429). Please try again in a moment.',
                        ];
                    }
                } else {
                    return [
                        'success' => false,
                        'error' => 'Pollinations free: HTTP ' . $statusCode . ' - ' . substr($response->body(), 0, 200),
                    ];
                }
            }

            $imageData = $response->body();
            if (strlen($imageData) < 1000) {
                return ['success' => false, 'error' => 'Pollinations free: returned invalid/empty image data'];
            }

            // Detect mime type from the response
            $contentType = $response->header('Content-Type') ?? '';
            if (str_contains($contentType, 'png')) {
                $mimeType = 'image/png';
            } elseif (str_contains($contentType, 'jpeg') || str_contains($contentType, 'jpg')) {
                $mimeType = 'image/jpeg';
            } else {
                // Check magic bytes
                $header = substr($imageData, 0, 4);
                if ($header === "\x89PNG") {
                    $mimeType = 'image/png';
                } else {
                    $mimeType = 'image/jpeg';
                }
            }

            Log::info('Pollinations free: successfully generated image', ['size' => strlen($imageData), 'mime' => $mimeType]);

            return [
                'success' => true,
                'image_data' => $imageData,
                'mime_type' => $mimeType,
            ];

        } catch (\Exception $e) {
            return ['success' => false, 'error' => 'Pollinations free exception: ' . $e->getMessage()];
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

    /**
     * Get attribution/citation text for the provider used.
     */
    protected function getProviderCitation(string $provider): string
    {
        return match ($provider) {
            'nano_banana' => 'Generated with Google Gemini (Nano Banana). Royalty-free for commercial use.',
            'dalle' => 'Generated with OpenAI DALL-E 3. Royalty-free for commercial use per OpenAI terms.',
            'gpt_image' => 'Generated with OpenAI GPT Image. Royalty-free for commercial use per OpenAI terms.',
            'flux' => 'Generated with FLUX via Together AI. Royalty-free for commercial use.',
            'pollinations' => 'Generated with Pollinations AI (FLUX). Royalty-free for commercial use.',
            'pollinations_free' => 'Generated with Pollinations AI (free tier). Image may contain watermark. Royalty-free for commercial use.',
            default => 'AI-generated image. Royalty-free for commercial use.',
        };
    }
}
