<?php

namespace Jacksmall\LaravelRatelimiter\Providers;

use Illuminate\Support\ServiceProvider;
use Jacksmall\LaravelRatelimiter\Services\RateLimiterManager;
use Jacksmall\LaravelRatelimiter\Services\TokenBucketRateLimiter;

class RateLimiterServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../../config/rate_limiter.php', 'rate_limiter'
        );
        // 绑定限流管理器
        $this->app->singleton('rate_limiter', function ($app) {
            return new RateLimiterManager($app);
        });
        // 绑定令牌桶限流器
        $this->app->singleton('token_bucket_rate_limiter', function ($app) {
            return function ($strategy, $identifier, $config = []) use ($app) {
                $defaults = config('rate_limiter.defaults');
                $serviceConfig = $this->getServiceConfig($strategy, $identifier);
                $config = array_merge($defaults, $serviceConfig, $config);

                return $this->app->make(TokenBucketRateLimiter::class, [
                    'strategy' => $strategy,
                    'identifier' => $identifier,
                    'config' => $config
                ]);
            };
        });
    }

    public function boot()
    {
        // 发布配置文件
        $this->publishes([
            __DIR__.'/../../config/rate_limiter.php' => config_path('rate_limiter.php'),
        ], 'rate-limiter-config');
    }

    /**
     * 获取服务特定配置
     */
    protected function getServiceConfig($strategy, $identifier)
    {
        if ($strategy === TokenBucketRateLimiter::STRATEGY_SERVICE && $identifier) {
            return config("rate_limiter.services.{$identifier}", []);
        }

        return [];
    }
}