<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\OrderQueueController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\RoleController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:api')->group(function () {

    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/refresh', [AuthController::class, 'refresh']);

    Route::get('/users', [UserController::class, 'index']);
    Route::put('/users', [UserController::class, 'update']);
    Route::delete('/users', [UserController::class, 'destroy']);
    Route::post('/users/assign-role', [UserController::class, 'assignRole']);
    Route::post('/users/remove-role', [UserController::class, 'removeRole']);

    Route::get('/roles', [RoleController::class, 'index']);
    Route::post('/roles', [RoleController::class, 'store']);
    Route::put('/roles', [RoleController::class, 'update']);
    Route::delete('/roles', [RoleController::class, 'destroy']);

    Route::get('/products', [ProductController::class, 'index']);
    Route::get('/products/{id}', [ProductController::class, 'show']);
    Route::post('/products', [ProductController::class, 'store']);
    Route::put('/products', [ProductController::class, 'update']);
    Route::delete('/products', [ProductController::class, 'destroy']);

    // Sync path (direct processing)
    Route::post('/orders/test/no-limit/unsafe', [OrderController::class, 'storeSyncUnsafe']);
    Route::post('/orders/test/no-limit/safe', [OrderController::class, 'storeSyncSafe']);

    Route::middleware('throttle:orders')->group(function () {
        Route::post('/orders/test/with-limit/unsafe', [OrderController::class, 'storeSyncUnsafe']);
        Route::post('/orders/test/with-limit/safe', [OrderController::class, 'storeSyncSafe']);
    });

    // Async path (queued)
    Route::prefix('orders/queue/with-limit')
        ->middleware('throttle:orders-queue')
        ->group(function () {
            Route::post('/unsafe', [OrderQueueController::class, 'storeAsyncUnsafe']);
            Route::post('/safe', [OrderQueueController::class, 'storeAsyncSafe']);
        });

    Route::prefix('orders/queue/no-limit')
        ->group(function () {
            Route::post('/unsafe', [OrderQueueController::class, 'storeAsyncUnsafe']);
            Route::post('/safe', [OrderQueueController::class, 'storeAsyncSafe']);
        });

    // Order CRUD (shared between sync/async)
    Route::get('/orders', [OrderController::class, 'index']);
    Route::get('/orders/my', [OrderController::class, 'myOrders']);
    Route::get('/orders/{id}', [OrderController::class, 'show']);
    Route::put('/orders', [OrderController::class, 'update']);
    Route::delete('/orders', [OrderController::class, 'destroy']);
});

Route::fallback(function () {
    return response()->json([
        'message' => 'Route Not Found'
    ], 404);
});