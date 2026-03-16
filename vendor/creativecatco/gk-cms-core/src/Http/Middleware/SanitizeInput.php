<?php

namespace CreativeCatCo\GkCmsCore\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * SanitizeInput Middleware
 *
 * Sanitizes incoming request data to prevent XSS attacks.
 * Strips dangerous HTML tags and JavaScript from text inputs.
 *
 * Exceptions:
 * - Fields explicitly allowed to contain HTML (custom_template, custom_css,
 *   custom_head_code, custom_body_code, global_css, custom_font_embed, content)
 *   are NOT sanitized to preserve admin-intended code.
 * - File uploads are not affected.
 */
class SanitizeInput
{
    /**
     * Fields that are allowed to contain raw HTML/JS (admin-only fields).
     */
    protected array $htmlAllowedFields = [
        'custom_template',
        'custom_css',
        'custom_head_code',
        'custom_body_code',
        'global_css',
        'custom_font_embed',
        'content',
        'body',
        'blocks',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        // Only sanitize POST/PUT/PATCH requests
        if (in_array($request->method(), ['POST', 'PUT', 'PATCH'])) {
            $input = $request->all();
            $sanitized = $this->sanitizeArray($input);
            $request->merge($sanitized);
        }

        return $next($request);
    }

    protected function sanitizeArray(array $data, string $parentKey = ''): array
    {
        foreach ($data as $key => $value) {
            $fullKey = $parentKey ? "{$parentKey}.{$key}" : $key;

            // Skip HTML-allowed fields
            if (in_array($key, $this->htmlAllowedFields)) {
                continue;
            }

            if (is_array($value)) {
                $data[$key] = $this->sanitizeArray($value, $fullKey);
            } elseif (is_string($value)) {
                // Remove script tags and event handlers, but allow basic HTML
                $data[$key] = $this->sanitizeString($value);
            }
        }

        return $data;
    }

    protected function sanitizeString(string $value): string
    {
        // Remove <script> tags and their content
        $value = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', '', $value);

        // Remove javascript: protocol in href/src attributes
        $value = preg_replace('/\b(href|src)\s*=\s*["\']?\s*javascript\s*:/i', '$1="', $value);

        // Remove on* event handlers (onclick, onload, onerror, etc.)
        $value = preg_replace('/\bon\w+\s*=\s*["\'][^"\']*["\']/i', '', $value);
        $value = preg_replace('/\bon\w+\s*=\s*[^\s>]*/i', '', $value);

        return $value;
    }
}
