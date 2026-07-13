<?php
namespace App\Core;

use FastRoute\Dispatcher;
use App\Core\Exceptions\ExceptionHandler;

class Kernel
{
    private $container;
    private $middlewareAliases = [
        'auth' => \App\Middleware\AuthMiddleware::class,
        'throttle' => \App\Middleware\RateLimitMiddleware::class,
    ];

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function handle(Request $request)
    {
        try {
            // Fetch routes
            $dispatcher = require __DIR__ . '/../../routes/api.php';
            
            $httpMethod = $request->method();
            $uri = $request->uri();
            
            if (false !== $pos = strpos($uri, '?')) {
                $uri = substr($uri, 0, $pos);
            }
            $uri = rawurldecode($uri);
            
            $routeInfo = $dispatcher->dispatch($httpMethod, $uri);
            
            switch ($routeInfo[0]) {
                case Dispatcher::NOT_FOUND:
                    // Fallback to old files
                    $oldFilePath = __DIR__ . '/../../' . $uri . '.php';
                    if (file_exists($oldFilePath)) {
                        
                            $executeLegacy = function($req) use ($oldFilePath) {
                                ob_start();
                                require $oldFilePath;
                                $output = ob_get_clean();
                                $statusCode = http_response_code() ?: 200;
                                return new Response($output, $statusCode, ['Content-Type' => 'application/json']);
                            };

                        // Apply Rate Limiting globally to Auth routes in fallback
                        if (strpos($uri, '/api/auth/') === 0) {
                            $throttle = $this->container->resolve(\App\Middleware\RateLimitMiddleware::class);
                            return $throttle->handle($request, $executeLegacy);
                        }
                        
                        return $executeLegacy($request);
                    }
                    return Response::error('404 Not Found', 404);
                    
                case Dispatcher::METHOD_NOT_ALLOWED:
                    return Response::error('405 Method Not Allowed', 405);
                    
                case Dispatcher::FOUND:
                    $routeData = $routeInfo[1];
                    $vars = $routeInfo[2];
                    
                    if (isset($routeData['handler'])) {
                        $handler = $routeData['handler'];
                        $middlewares = $routeData['middleware'] ?? [];
                    } else {
                        $handler = $routeData;
                        $middlewares = [];
                    }
            
                    list($class, $method) = $handler;
                    
                    // Build Middleware Pipeline
                    $next = function($req) use ($class, $method, $vars) {
                        $controller = $this->container->resolve($class);
                        $response = call_user_func_array([$controller, $method], array_merge([$req], $vars));
                        
                        if (is_array($response)) {
                            return Response::json($response);
                        }
                        if (!$response instanceof Response) {
                            return new Response($response);
                        }
                        return $response;
                    };
            
                    // Chain middlewares backwards
                    foreach (array_reverse($middlewares) as $middlewareName) {
                        $middlewareClass = $this->middlewareAliases[$middlewareName] ?? $middlewareName;
                        $middleware = $this->container->resolve($middlewareClass);
                        $next = function($req) use ($middleware, $next) {
                            return $middleware->handle($req, $next);
                        };
                    }
            
                    // Execute pipeline
                    return $next($request);
            }
        } catch (\Throwable $e) {
            return ExceptionHandler::handle($e);
        }
    }
}
