<?php

namespace App\Traits;

trait ApiResponse
{
    private function isValidHttpCode(int $code): bool
    {
        return $code >= 100 && $code <= 599;
    }

    public function success($data = null, $message = null, $code = 200)
    {
        $code = (int) $code;
        if (!$this->isValidHttpCode($code)) {
            $code = 200;
        }
        return response()->json([
            'status' => true,
            'message' => $message,
            'data' => $data,
        ], $code);
    }

    public function error($message = null, $code = 400)
    {
        $code = (int) $code;
        if (!$this->isValidHttpCode($code)) {
            $code = 500;
        }
        return response()->json([
            'status' => false,
            'message' => $message,
        ], $code);
    }
}