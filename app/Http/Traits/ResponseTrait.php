<?php

namespace App\Http\Traits;

trait ResponseTrait
{
    public function success($msg, $data = null)
    {
        $response = [
            'status' => true,
            'message' => $msg,
            'data' => $data,
        ];
        return response($response);
    }

    public function failed($msg)
    {
        $response = [
            'status' => false,
            'message' => $msg,
            'data' => null,
        ];
        return response($response);
    }
}
