<?php
require_once __DIR__ . '/middleware/cors.php';

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = rtrim($uri, '/');
$method = $_SERVER['REQUEST_METHOD'];

// Route mapping
$routes = [
    // Auth
    'POST /api/auth/login' => 'api/auth/login.php',
    'POST /api/auth/register' => 'api/auth/register.php',
    'POST /api/auth/logout' => 'api/auth/logout.php',
    'GET /api/auth/me' => 'api/auth/me.php',
    
    // Issues
    'GET /api/issues' => 'api/issues/index.php',
    'POST /api/issues' => 'api/issues/create.php',
    'GET /api/issues/show' => 'api/issues/show.php',
    'PUT /api/issues/update' => 'api/issues/update.php',
    
    // Inventory
    'GET /api/inventory' => 'api/inventory/index.php',
    'POST /api/inventory' => 'api/inventory/create.php',
    'POST /api/inventory/update' => 'api/inventory/update.php',
    'DELETE /api/inventory/delete' => 'api/inventory/delete.php',
    
    // Borrows
    'GET /api/borrows' => 'api/borrows/index.php',
    'POST /api/borrows' => 'api/borrows/create.php',
    'PUT /api/borrows/update' => 'api/borrows/update.php',
    
    // Dashboard
    'GET /api/dashboard/stats' => 'api/dashboard/stats.php',
];

$routeKey = "$method $uri";

if (isset($routes[$routeKey])) {
    require_once __DIR__ . '/' . $routes[$routeKey];
} else {
    http_response_code(404);
    echo json_encode(['error' => 'Route not found', 'path' => $uri, 'method' => $method]);
}
