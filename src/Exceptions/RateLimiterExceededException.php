<?php

namespace Jacksmall\LaravelRatelimiter\Exceptions;

class RateLimiterExceededException extends \Exception
{
    protected $retryAfter;

    public function __construct($message = "", $code = 429, $retryAfter = 0, \Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->retryAfter = $retryAfter;
    }

    public function getRetryAfter(): float
    {
        return $this->retryAfter;
    }

    public function render($request)
    {
        return response()->json([
            'error' => 'rate_limit_exceeded',
            'message' => $this->getMessage(),
            'retry_after' => $this->retryAfter
        ], 429);
    }
}