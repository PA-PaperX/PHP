<?php
require __DIR__ . '/../vendor/autoload.php';

use App\Core\Container;
use App\Core\Request;
use App\Core\Kernel;
use App\Core\Exceptions\ExceptionHandler;

header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Set session cookie lifetime to 30 days and SameSite to Lax to prevent cross-port session drops
session_set_cookie_params([
    'lifetime' => 2592000,
    'path' => '/',
    'samesite' => 'Lax'
]);

set_exception_handler(function($e) {
    ExceptionHandler::handle($e)->send();
});

// --- BOOTSTRAP CONTAINER ---
$container = new Container();

// Bind PDO as Singleton
require_once __DIR__ . '/../config/database.php';
$container->singleton(PDO::class, (new \Database())->getConnection());

// Create and Bind Request
$request = new Request();
$container->singleton(Request::class, $request);

// --- KERNEL DISPATCH ---
$kernel = new Kernel($container);
$response = $kernel->handle($request);

// --- SEND RESPONSE ---
$response->send();
