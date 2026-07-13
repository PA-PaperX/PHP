<?php
namespace App\Core\Exceptions;

class UnauthorizedException extends AppException
{
    public function __construct($message = "Unauthorized", $code = 401, \Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
