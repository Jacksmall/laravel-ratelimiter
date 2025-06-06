<?php

namespace Jacksmall\LaravelRatelimiter\Services;

class RateLimiterManager
{
    protected $app;
    protected $limiterFactory;

    public function __construct($app)
    {
        $this->app = $app;
        $this->limiterFactory = $app->make('token_bucket_limiter');
    }

    /**
     * 创建限流器实例
     *
     * @param string $strategy
     * @param string $identifier
     * @param array $config
     * @return mixed
     */
    public function for(string $strategy, string $identifier = '', array $config = [])
    {
        return ($this->limiterFactory)($strategy, $identifier, $config);
    }

    /**
     * 尝试获取令牌
     *
     * @param string $strategy
     * @param string $identifier
     * @param int $cost
     * @return mixed
     */
    public function tryAcquire(string $strategy, string $identifier = '', int $cost = 1)
    {
        $limiter = $this->for($strategy, $identifier, ['cost' => $cost]);
        return $limiter->tryAcquire($cost);
    }

    /**
     * 获取限流器状态
     *
     * @param string $strategy
     * @param string $identifier
     * @return mixed
     */
    public function getStatus(string $strategy, string $identifier = '')
    {
        $limiter = $this->for($strategy, $identifier);
        return $limiter->getStatus();
    }

    /**
     * 重置限流器
     *
     * @param string $strategy
     * @param string $identifier
     * @return void
     */
    public function reset(string $strategy, string $identifier = '')
    {
        $this->for($strategy, $identifier)->reset();
    }
}