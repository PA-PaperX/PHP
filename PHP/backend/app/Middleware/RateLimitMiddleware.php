<?php
namespace App\Middleware;

use App\Core\Middleware\MiddlewareInterface;
use App\Core\Request;
use App\Core\Exceptions\AppException;

class RateLimitMiddleware implements MiddlewareInterface
{
    private $limit = 10; // Max requests
    private $window = 60; // Seconds

    public function handle(Request $request, callable $next)
    {
        $ip = $request->header('REMOTE_ADDR', '127.0.0.1');
        $key = 'rate_limit_' . md5($ip);
        $storageDir = __DIR__ . '/../../storage/rate_limit/';
        
        if (!is_dir($storageDir)) {
            mkdir($storageDir, 0777, true);
        }

        $file = $storageDir . $key . '.json';
        $currentTime = time();
        $requests = [];

        if (file_exists($file)) {
            $data = json_decode(file_get_contents($file), true) ?? [];
            // Filter old requests outside the window
            $requests = array_filter($data, function($timestamp) use ($currentTime) {
                return ($currentTime - $timestamp) < $this->window;
            });
        }

        if (count($requests) >= $this->limit) {
            throw new AppException("Too Many Requests. Please try again later.", 429);
        }

        $requests[] = $currentTime;
        file_put_contents($file, json_encode(array_values($requests)));

        return $next($request);
    }
}
