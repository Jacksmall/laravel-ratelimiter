<?php

namespace Jacksmall\LaravelRatelimiter\Services;

use Illuminate\Support\Facades\Redis;
class TokenBucketRateLimiter
{
    const STRATEGY_GLOBAL = 'global';
    const STRATEGY_SERVICE = 'service';
    const STRATEGY_USER = 'user';

    protected $key;
    protected $capacity;
    protected $rate;
    protected $cost;
    protected $coolDown;

    public function __construct(
        string $strategy,
        string $identifier = '',
        array $config = []
    ) {
        $this->key = $this->buildKey($strategy, $identifier);
        $this->capacity = $config['capacity'] ?? 100;
        $this->rate = $config['rate'] ?? 10;
        $this->cost = $config['cost'] ?? 1;
        $this->coolDown = $config['cool_down'] ?? 30;
    }

    /**
     * 尝试获取令牌
     */
    public function tryAcquire(): array
    {
        $now = microtime(true);
        $script = <<<'LUA'
local key = KEYS[1]
local now = tonumber(ARGV[1])
local cost = tonumber(ARGV[2])
local capacity = tonumber(ARGV[3])
local rate = tonumber(ARGV[4])
local coolDown = tonumber(ARGV[5])

-- 获取桶的当前状态
local bucket = redis.call('HMGET', key, 'tokens', 'last_time')
local tokens = capacity
local last_time = now

if bucket[1] then
    tokens = tonumber(bucket[1])
    last_time = tonumber(bucket[2])
end

-- 计算时间差和应添加的令牌
local elapsed = now - last_time
local fill_tokens = elapsed * rate
tokens = math.min(capacity, tokens + fill_tokens)

-- 判断是否允许请求
local allowed = false
local remaining = tokens
local reset_after = 0
local retry_after = 0

if tokens >= cost then
    -- 足够令牌，允许请求
    tokens = tokens - cost
    last_time = now
    allowed = true
    remaining = tokens
    reset_after = (capacity - tokens) / rate
else
    -- 令牌不足，计算需要等待的时间
    retry_after = (cost - tokens) / rate
    reset_after = (capacity - tokens) / rate
    
    -- 如果超过冷却时间，重置桶
    if elapsed > coolDown then
        tokens = capacity
        last_time = now
        reset_after = 0
        retry_after = 0
    end
end

-- 更新桶状态
redis.call('HMSET', key, 
    'tokens', tokens, 
    'last_time', last_time
)

-- 设置过期时间（避免冷数据长期占用内存）
local ttl = math.ceil(coolDown * 2)
redis.call('EXPIRE', key, ttl)

return { 
    allowed and 1 or 0, 
    remaining, 
    reset_after,
    retry_after
}
LUA;

        $result = Redis::eval(
            $script,
            1,
            $this->key,
            $now,
            $this->cost,
            $this->capacity,
            $this->rate,
            $this->coolDown
        );

        return [
            (bool)$result[0],
            (int)$result[1],
            (float)$result[2],
            (float)$result[3]
        ];
    }

    /**
     * 获取当前状态
     */
    public function getStatus(): array
    {
        $data = Redis::hgetall($this->key);

        $tokens = $data['tokens'] ?? $this->capacity;
        $lastTime = $data['last_time'] ?? microtime(true);

        $now = microtime(true);
        $elapsed = $now - (float)$lastTime;
        $currentTokens = min(
            $this->capacity,
            (float)$tokens + ($elapsed * $this->rate)
        );

        return [
            'key' => $this->key,
            'capacity' => $this->capacity,
            'current_tokens' => $currentTokens,
            'rate' => $this->rate,
            'last_updated' => (float)$lastTime,
            'next_refresh' => max(0, ($this->capacity - $currentTokens) / $this->rate),
            'ttl' => Redis::ttl($this->key)
        ];
    }

    /**
     * 重置限流器
     */
    public function reset(): void
    {
        Redis::del($this->key);
    }

    /**
     * 构建 Redis 键
     */
    protected function buildKey(string $strategy, string $identifier): string
    {
        $prefix = config('rate_limiter.prefix', 'rate_limit');

        switch ($strategy) {
            case self::STRATEGY_SERVICE:
                return "{$prefix}:service:{$identifier}";
            case self::STRATEGY_USER:
                return "{$prefix}:user:{$identifier}";
            default:
                return "{$prefix}:global";
        }
    }
}