<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class PerformanceMiddleware
{
    /**
     * Handle an incoming request with performance optimizations
     */
    public function handle($request, Closure $next)
    {
        // Skip for API requests that need real-time data
        if ($request->is('api/*')) {
            return $next($request);
        }

        // Generate cache key based on URL and user
        $cacheKey = 'page_cache:' . md5($request->fullUrl() . ':' . (auth()->check() ? auth()->id() : 'guest'));
        
        // Try to get cached response (5 minute TTL)
        if (config('app.env') === 'production' && !$request->user()?->is_admin) {
            $cachedResponse = Cache::remember($cacheKey, 300, function () use ($next, $request) {
                return serialize($next($request));
            });
            
            if ($cachedResponse) {
                $response = unserialize($cachedResponse);
                $response->header('X-Cache', 'HIT');
                return $response;
            }
        }

        $response = $next($request);
        $response->header('X-Cache', 'MISS');
        
        return $response;
    }
}
