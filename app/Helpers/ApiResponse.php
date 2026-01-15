<?php

namespace App\Helpers;

class ApiResponse
{
    public static function success($data, $message = null, $code = 200)
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data
        ], $code);
    }

    public static function error($message, $code = 400, $errors = null)
    {
        return response()->json([
            'success' => false,
            'error' => [
                'code' => $code,
                'message' => $message,
                'errors' => $errors
            ]
        ], $code);
    }
}
