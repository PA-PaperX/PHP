<?php
namespace App\Core\Middleware;

use App\Core\Request;

interface MiddlewareInterface
{
    /**
     * Handle the incoming request.
     * 
     * @param Request $request
     * @param callable $next
     * @return mixed
     */
    public function handle(Request $request, callable $next);
}
