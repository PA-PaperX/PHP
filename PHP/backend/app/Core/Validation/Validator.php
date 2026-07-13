<?php
namespace App\Core\Validation;

use App\Core\Request;
use App\Core\Exceptions\ValidationException;

class Validator
{
    public static function validate(Request $request, array $rules)
    {
        $errors = [];
        $validatedData = [];

        foreach ($rules as $field => $ruleString) {
            $value = $request->input($field);
            $rulesArray = explode('|', $ruleString);
            
            foreach ($rulesArray as $rule) {
                if ($rule === 'required' && empty($value)) {
                    $errors[$field][] = "The {$field} field is required.";
                }
                
                if ($rule === 'numeric' && !empty($value) && !is_numeric($value)) {
                    $errors[$field][] = "The {$field} must be a number.";
                }
            }

            if (!isset($errors[$field])) {
                $validatedData[$field] = $value;
            }
        }

        if (!empty($errors)) {
            throw new ValidationException($errors);
        }

        return $validatedData;
    }
}
