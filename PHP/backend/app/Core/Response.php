<?php
namespace App\Core;

class Response
{
    public $data;
    public $status;
    public $headers = [];

    public function __construct($data, $status = 200, $headers = ['Content-Type' => 'application/json'])
    {
        $this->data = $data;
        $this->status = $status;
        $this->headers = $headers;
    }

    public function send()
    {
        http_response_code($this->status);
        foreach ($this->headers as $key => $value) {
            header("$key: $value");
        }
        if (is_array($this->data) || is_object($this->data)) {
            echo json_encode($this->data);
        } else {
            echo $this->data;
        }
    }

    public static function json($data, $status = 200)
    {
        return new self($data, $status);
    }

    public static function success($data = [], $message = "Success", $status = 200)
    {
        return self::json([
            'success' => true,
            'message' => $message,
            'data' => $data
        ], $status);
    }

    public static function error($message = "Error occurred", $status = 400, $errors = [])
    {
        return self::json([
            'success' => false,
            'message' => $message,
            'errors' => $errors
        ], $status);
    }
}
