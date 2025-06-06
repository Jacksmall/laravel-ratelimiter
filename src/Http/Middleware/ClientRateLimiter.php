<?php

namespace Jacksmall\LaravelRatelimiter\Http\Middleware;

use Jacksmall\LaravelRatelimiter\Exceptions\RateLimiterExceededException;
use Jacksmall\LaravelRatelimiter\Services\TokenBucketRateLimiter;
use GuzzleHttp\Promise\RejectedPromise;
use Psr\Http\Message\RequestInterface;
class ClientRateLimiter
{
    /**
     * 处理 Guzzle 请求
     */
    public function __invoke(callable $handler)
    {
        return function (RequestInterface $request, array $options) use ($handler) {
            $service = $this->determineService($request);
            $limiter = $this->createLimiter($service);

            [$allowed, $remaining, $resetAfter, $retryAfter] = $limiter->tryAcquire();

            if (!$allowed) {
                return new RejectedPromise(
                    new RateLimiterExceededException(
                        "Rate limit exceeded for service: {$service}",
                        429,
                        $retryAfter
                    )
                );
            }

            $status = $limiter->getStatus();
            // 添加限流头信息
            $request = $request->withHeader('X-RateLimit-Limit', $status['capacity'])
                ->withHeader('X-RateLimit-Remaining', $remaining)
                ->withHeader('X-RateLimit-Reset', $resetAfter);

            return $handler($request, $options);
        };
    }

    /**
     * 确定服务标识
     */
    protected function determineService(RequestInterface $request): string
    {
        $host = $request->getUri()->getHost();

        // 从配置中获取服务映射
        $serviceMap = config('rate_limiter.http_client.services', []);

        foreach ($serviceMap as $service => $domain) {
            if (str_contains($host, $domain)) {
                return $service;
            }
        }

        return 'global';
    }

    /**
     * 创建限流器
     */
    protected function createLimiter(string $service): TokenBucketRateLimiter
    {
        $config = config("rate_limiter.http_client.services.{$service}", []);

        if ($service === 'global') {
            $config = config("rate_limiter.http_client.global", []);
        }

        return app('token_bucket_limiter')(
            TokenBucketRateLimiter::STRATEGY_SERVICE,
            "http_{$service}",
            $config
        );
    }
}