<?php

namespace CreativeCatCo\GkCmsCore\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiTokenAuth
{
    /**
     * Handle an incoming request.
     *
     * Validates the Bearer token against the CMS_API_TOKEN environment variable.
     * This allows the AI chatbot to authenticate without a browser session.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = config('cms.api_token') ?: env('CMS_API_TOKEN');

        if (empty($token)) {
            return response()->json([
                'message' => 'API token not configured. Set CMS_API_TOKEN in your .env file.',
            ], 500);
        }

        $bearerToken = $request->bearerToken();

        if (!$bearerToken || $bearerToken !== $token) {
            return response()->json([
                'message' => 'Unauthorized. Invalid or missing API token.',
            ], 401);
        }

        return $next($request);
    }
}
