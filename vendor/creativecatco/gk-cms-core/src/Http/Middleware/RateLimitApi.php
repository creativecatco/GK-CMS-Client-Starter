<?php

namespace CreativeCatCo\GkCmsCore\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Cache\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

/**
 * RateLimitApi Middleware
 *
 * Applies rate limiting to CMS API endpoints to prevent:
 * - Brute force attacks on API token auth
 * - Abuse of write endpoints (field updates, uploads)
 * - DDoS amplification through expensive operations
 *
 * Limits: 60 requests per minute per IP for authenticated users,
 *         30 requests per minute per IP for unauthenticated requests.
 */
class RateLimitApi
{
    public function __construct(
        protected RateLimiter $limiter
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $key = 'cms_api_' . ($request->user()?->id ?? $request->ip());
        $maxAttempts = $request->user() ? 60 : 30;

        if ($this->limiter->tooManyAttempts($key, $maxAttempts)) {
            return response()->json([
                'message' => 'Too many requests. Please try again later.',
                'retry_after' => $this->limiter->availableIn($key),
            ], 429);
        }

        $this->limiter->hit($key, 60); // 60 second decay

        $response = $next($request);

        $response->headers->set('X-RateLimit-Limit', $maxAttempts);
        $response->headers->set('X-RateLimit-Remaining', max(0, $maxAttempts - $this->limiter->attempts($key)));

        return $response;
    }
}
