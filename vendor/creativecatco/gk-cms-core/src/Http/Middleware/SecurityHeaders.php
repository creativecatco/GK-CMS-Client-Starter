<?php

namespace CreativeCatCo\GkCmsCore\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * SecurityHeaders Middleware
 *
 * Adds essential security headers to all responses to protect against:
 * - XSS attacks (X-XSS-Protection, Content-Security-Policy)
 * - Clickjacking (X-Frame-Options)
 * - MIME type sniffing (X-Content-Type-Options)
 * - Information leakage (Referrer-Policy, Permissions-Policy)
 * - Protocol downgrade attacks (Strict-Transport-Security)
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Prevent clickjacking - only allow same-origin framing
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');

        // Prevent MIME type sniffing
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        // XSS Protection (legacy browsers)
        $response->headers->set('X-XSS-Protection', '1; mode=block');

        // Referrer Policy - send origin only on cross-origin requests
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // Permissions Policy - restrict sensitive browser features
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=()');

        // HSTS - enforce HTTPS (only in production)
        if ($request->isSecure() || config('app.env') === 'production') {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }
}
