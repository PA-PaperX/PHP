<?php
namespace App\Core\Exceptions;

class ValidationException extends AppException
{
    private $errors;

    public function __construct($errors = [], $message = "Validation Failed")
    {
        $this->errors = $errors;
        parent::__construct($message, 422);
    }

    public function getErrors()
    {
        return $this->errors;
    }
}
