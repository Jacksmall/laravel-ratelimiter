<?php

namespace Jacksmall\LaravelRatelimiter\Facades;

use Illuminate\Support\Facades\Facade;
use Jacksmall\LaravelRatelimiter\Services\TokenBucketRateLimiter;

/**
 * @method static TokenBucketRateLimiter for(string $strategy, string $identifier = '', array $config = [])
 * @method static bool tryAcquire(string $strategy, string $identifier = '', int $cost = 1)
 * @method static array getStatus(string $strategy, string $identifier = '')
 * @method static void reset(string $strategy, string $identifier = '')
 */
class RateLimiter extends Facade
{
    public static function getFacadeAccessor()
    {
        return 'rate_limiter';
    }
}