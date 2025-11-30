<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class ApiRateLimiter
 *
 * Custom rate limiting middleware for API endpoints.
 */
class ApiRateLimiter
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string $limit = 'default'): Response
    {
        $key = $this->resolveRequestSignature($request);

        $limits = [
            'default' => [60, 1], // 60 requests per minute
            'auth' => [5, 1],      // 5 requests per minute for auth endpoints
            'checkout' => [10, 1], // 10 requests per minute for checkout
            'search' => [30, 1],   // 30 requests per minute for search
        ];

        [$maxAttempts, $decayMinutes] = $limits[$limit] ?? $limits['default'];

        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            return response()->json([
                'message' => 'Too many requests. Please try again later.',
            ], 429);
        }

        RateLimiter::hit($key, $decayMinutes * 60);

        $response = $next($request);

        return $response->withHeaders([
            'X-RateLimit-Limit' => $maxAttempts,
            'X-RateLimit-Remaining' => RateLimiter::remaining($key, $maxAttempts),
        ]);
    }

    /**
     * Resolve request signature for rate limiting.
     */
    protected function resolveRequestSignature(Request $request): string
    {
        if ($user = $request->user()) {
            return 'api-user-'.$user->id;
        }

        return 'api-ip-'.$request->ip();
    }
}
