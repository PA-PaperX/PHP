<?php
namespace App\Core;

class Request
{
    private $query;
    private $request;
    private $server;
    private $jsonPayload;
    private $attributes = []; // Store middleware attributes like User

    public function __construct()
    {
        $this->query = $_GET;
        $this->request = $_POST;
        $this->server = $_SERVER;
        
        $this->jsonPayload = [];
        if (strpos($this->header('Content-Type', ''), 'application/json') !== false) {
            $input = file_get_contents('php://input');
            if ($input) {
                $this->jsonPayload = json_decode($input, true) ?? [];
            }
        }
    }

    public function input($key, $default = null)
    {
        return $this->jsonPayload[$key] ?? $this->request[$key] ?? $this->query[$key] ?? $default;
    }

    public function all()
    {
        return array_merge($this->query, $this->request, $this->jsonPayload);
    }

    public function header($key, $default = null)
    {
        $serverKey = 'HTTP_' . strtoupper(str_replace('-', '_', $key));
        return $this->server[$serverKey] ?? $this->server[$key] ?? $default;
    }

    public function method()
    {
        return $this->server['REQUEST_METHOD'] ?? 'GET';
    }

    public function uri()
    {
        return $this->server['REQUEST_URI'] ?? '/';
    }

    // Pass data between middlewares and controllers
    public function setAttribute($key, $value)
    {
        $this->attributes[$key] = $value;
    }

    public function attribute($key, $default = null)
    {
        return $this->attributes[$key] ?? $default;
    }
}
