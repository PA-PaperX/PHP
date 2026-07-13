<?php
namespace App\Middleware;

use App\Core\Middleware\MiddlewareInterface;
use App\Core\Request;
use App\Core\Exceptions\UnauthorizedException;

class AuthMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, callable $next)
    {
        require_once __DIR__ . '/../../middleware/auth.php';
        
        try {
            // getCurrentUser() throws Exception if unauthorized in legacy code
            $user = getCurrentUser(); 
            
            // Magic! Pass the user to the controller via the Request object
            $request->setAttribute('user', $user);
        } catch (\Exception $e) {
            throw new UnauthorizedException($e->getMessage());
        }

        return $next($request);
    }
}
