<?php
namespace App\Controllers;

use App\Core\Response;

abstract class Controller
{
    protected function json($data, $status = 200)
    {
        return Response::json($data, $status);
    }

    protected function success($data = [], $message = "Success", $status = 200)
    {
        return Response::success($data, $message, $status);
    }

    protected function error($message = "Error", $status = 400, $errors = [])
    {
        return Response::error($message, $status, $errors);
    }
}
