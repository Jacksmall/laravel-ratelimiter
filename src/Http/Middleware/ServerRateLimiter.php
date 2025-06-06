<?php

namespace Jacksmall\LaravelRatelimiter\Http\Middleware;

use Jacksmall\LaravelRatelimiter\Exceptions\RateLimiterExceededException;
use Jacksmall\LaravelRatelimiter\Facades\RateLimiter;
use Closure;
use Illuminate\Http\Request;
use Jacksmall\LaravelRatelimiter\Services\TokenBucketRateLimiter;
use Symfony\Component\HttpFoundation\Response;
class ServerRateLimiter
{
    /**
     * 处理传入请求
     */
    public function handle(Request $request, Closure $next, string $strategy, string $identifier = null, int $cost = 1): Response
    {
        $id = $this->getIdentifier($request, $strategy, $identifier);

        if (!RateLimiter::tryAcquire($strategy, $id, $cost)) {
            $status = RateLimiter::getStatus($strategy, $id);
            $retryAfter = ceil($status['next_refresh']);

            return response()->json([
                'error' => 'rate_limit_exceeded',
                'message' => 'Too many requests. Please try again later.',
                'retry_after' => $retryAfter
            ], 429)->header('Retry-After', $retryAfter);
        }

        $response = $next($request);

        // 添加限流头信息
        $status = RateLimiter::getStatus($strategy, $id);
        return $response->header('X-RateLimit-Limit', $status['capacity'])
            ->header('X-RateLimit-Remaining', $status['current_tokens'])
            ->header('X-RateLimit-Reset', $status['next_refresh']);
    }

    /**
     * 获取限流标识符
     */
    protected function getIdentifier(Request $request, string $strategy, ?string $identifier): string
    {
        if ($strategy === TokenBucketRateLimiter::STRATEGY_USER) {
            return $request->user()->id ?: $request->ip();
        }

        return $identifier ?: 'default';
    }
}