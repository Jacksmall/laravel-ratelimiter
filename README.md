# Laravel Rate Limiter
<hr>

### 1.安装
```shell
composer require jacksmall/laravel-ratelimiter
```
### 2.配置
在 config/app.php 中添加服务提供者：
```php
'providers' => [
    // ...
    Jacksmall\LaravelRatelimiter\Providers\RateLimiterServiceProvider::class,
],

'aliases' => [
    // ...
    'RateLimiter' => Jacksmall\LaravelRatelimiter\Facades\RateLimiter::class,
],
```
### 3.发布配置文件
```shell
php artisan vendor:publish --provider="Jacksmall\LaravelRatelimiter\Providers\RateLimiterServiceProvider" --tag='rate-limiter-config'
```
### 4.使用
在控制器中使用
```php
use RateLimiter;

public function processPayment(Request $request)
{
    // 检查支付服务限流
    if (!RateLimiter::tryAcquire('service', 'payment-gateway')) {
        abort(429, 'Too many payment requests');
    }
    
    // 处理支付逻辑
    // ...
}
```
在路由中使用服务器端限流
```php
use Jacksmall\LaravelRatelimiter\Http\Middleware\ServerRateLimiter;

Route::middleware([
    ServerRateLimiter::class . ':service,payment-gateway,2'
])->group(function () {
    Route::post('/payments', 'PaymentController@process');
});
```
在HTTP客户端中使用
```php
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use Jacksmall\LaravelRatelimiter\Http\Middleware\ClientRateLimiter;

$handlerStack = HandlerStack::create();
$handlerStack->push(new ClientRateLimiter());

$client = new Client([
    'handler' => $handlerStack,
    'base_uri' => 'https://api.example.com'
]);

try {
    $response = $client->get('/data');
} catch (RateLimitExceededException $e) {
    // 处理限流异常
}
```
## enjoy it!😄