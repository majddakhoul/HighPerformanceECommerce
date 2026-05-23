<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenBlacklistedException;
use Tymon\JWTAuth\Exceptions\JWTException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Illuminate\Validation\ValidationException;

class Handler extends ExceptionHandler
{
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    public function render($request, Throwable $exception)
    {
        if ($request->expectsJson() || $request->is('api/*')) {
            return $this->handleApiException($request, $exception);
        }

        return parent::render($request, $exception);
    }

    protected function handleApiException($request, Throwable $exception)
    {
        if ($exception instanceof TokenInvalidException) {
            return response()->json([
                'status' => false,
                'message' => 'Token is invalid',
            ], 401);
        }

        if ($exception instanceof TokenExpiredException) {
            return response()->json([
                'status' => false,
                'message' => 'Token has expired',
            ], 401);
        }

        if ($exception instanceof TokenBlacklistedException) {
            return response()->json([
                'status' => false,
                'message' => 'Token is blacklisted',
            ], 401);
        }

        if ($exception instanceof JWTException) {
            return response()->json([
                'status' => false,
                'message' => 'Token error: ' . $exception->getMessage(),
            ], 401);
        }

        if ($exception instanceof AuthenticationException) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthenticated. Token required or invalid.',
            ], 401);
        }

        if ($exception instanceof AuthorizationException) {
            return response()->json([
                'status' => false,
                'message' => $exception->getMessage() ?: 'Forbidden - You do not have permission.',
            ], 403);
        }

        if ($exception instanceof ModelNotFoundException) {
            $model = class_basename($exception->getModel());
            return response()->json([
                'status' => false,
                'message' => "{$model} not found.",
            ], 404);
        }

        if ($exception instanceof NotFoundHttpException) {
            return response()->json([
                'status' => false,
                'message' => 'The requested endpoint does not exist.',
            ], 404);
        }

        if ($exception instanceof MethodNotAllowedHttpException) {
            return response()->json([
                'status' => false,
                'message' => 'Method not allowed for this endpoint.',
            ], 405);
        }

        if ($exception instanceof ValidationException) {
            return response()->json([
                'status' => false,
                'message' => 'Validation error',
                'errors' => $exception->errors(),
            ], 422);
        }

        $debug = config('app.debug');
        $response = [
            'status' => false,
            'message' => $exception->getMessage() ?: 'Server internal error',
        ];

        if ($debug) {
            $response['exception'] = get_class($exception);
            $response['file'] = $exception->getFile();
            $response['line'] = $exception->getLine();
        }

        return response()->json($response, 500);
    }
}