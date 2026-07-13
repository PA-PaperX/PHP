<?php
namespace App\Core\Exceptions;

use App\Core\Response;

class ExceptionHandler
{
    public static function handle(\Throwable $e)
    {
        $status = 500;
        $message = "Internal Server Error";
        $errors = [];
        
        $isDebug = getenv('APP_DEBUG') !== 'false';
        
        if ($e instanceof ValidationException) {
            $status = $e->getStatusCode();
            $message = $e->getMessage();
            $errors = $e->getErrors();
        } else if ($e instanceof AppException) {
            $status = $e->getStatusCode();
            $message = $e->getMessage();
        } else if ($e instanceof \PDOException) {
            $message = $isDebug ? "Database Error: " . $e->getMessage() : "Database Error";
        } else {
            $message = $isDebug ? $e->getMessage() : "Internal Server Error";
        }

        $errorData = [];
        if ($isDebug && !($e instanceof ValidationException)) {
            $errorData = [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ];
        }

        $response = Response::error($message, $status, $errors ?: $errorData);
        return $response;
    }
}
