<?php
namespace App\Core\Exceptions;

use Exception;

class AppException extends Exception
{
    protected $statusCode = 500;

    public function __construct($message = "An unexpected error occurred", $code = 500, Exception $previous = null)
    {
        $this->statusCode = $code;
        parent::__construct($message, $code, $previous);
    }

    public function getStatusCode()
    {
        return $this->statusCode;
    }
}
