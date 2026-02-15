<?php

namespace App\Builder;

class ApiResponse
{
     public static function success($data = null, $message = "", $status = 200)
    {
        return response()->json(
            [
                "error" => false,
                "message" => $message,
                "data" => $data
            ],
            $status
        );
    }

    public static function error($message = "", $data = null, $status = 400)
    {

        return response()->json(
            [
                "error" => true,
                "message" => $message,
                "data" => $data
            ],
            $status
        );
    }
}
