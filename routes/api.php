<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\OrderQueueController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\RoleController;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register'])->name('auth.register');
Route::post('/login', [AuthController::class, 'login'])->name('auth.login');

Route::middleware('auth:api')->group(function () {

    Route::get('/me', [AuthController::class, 'me'])->name('auth.me');
    Route::post('/logout', [AuthController::class, 'logout'])->name('auth.logout');
    Route::post('/refresh', [AuthController::class, 'refresh'])->name('auth.refresh');

    Route::get('/users', [UserController::class, 'index'])->name('users.list');
    Route::put('/users', [UserController::class, 'update'])->name('users.update');
    Route::delete('/users', [UserController::class, 'destroy'])->name('users.delete');
    Route::post('/users/assign-role', [UserController::class, 'assignRole'])->name('users.assign-role');
    Route::post('/users/remove-role', [UserController::class, 'removeRole'])->name('users.remove-role');

    Route::get('/roles', [RoleController::class, 'index'])->name('roles.list');
    Route::post('/roles', [RoleController::class, 'store'])->name('roles.create');
    Route::put('/roles', [RoleController::class, 'update'])->name('roles.update');
    Route::delete('/roles', [RoleController::class, 'destroy'])->name('roles.delete');

    Route::get('/products/top', [CartController::class, 'topProducts'])->name('products.top');
    Route::get('/products', [ProductController::class, 'index'])->name('products.list');
    Route::get('/products/{id}', [ProductController::class, 'show'])->name('products.show');
    Route::post('/products', [ProductController::class, 'store'])->name('products.create');
    Route::put('/products', [ProductController::class, 'update'])->name('products.update');
    Route::delete('/products', [ProductController::class, 'destroy'])->name('products.delete');

    Route::post('/products/optimistic/decrement/unsafe', [ProductController::class, 'optimisticDecrementUnsafe'])->name('products.optimistic.unsafe');
    Route::post('/products/optimistic/decrement/safe', [ProductController::class, 'optimisticDecrementSafe'])->name('products.optimistic.safe');

    Route::post('/orders/pessimistic/test/no-limit/unsafe', [OrderController::class, 'storeSyncUnsafe'])->name('orders.pessimistic.sync.unsafe.no-limit');
    Route::post('/orders/pessimistic/test/no-limit/safe', [OrderController::class, 'storeSyncSafe'])->name('orders.pessimistic.sync.safe.no-limit');

    Route::middleware('throttle:orders')->group(function () {
        Route::post('/orders/pessimistic/test/with-limit/unsafe', [OrderController::class, 'storeSyncUnsafe'])->name('orders.pessimistic.sync.unsafe.with-limit');
        Route::post('/orders/pessimistic/test/with-limit/safe', [OrderController::class, 'storeSyncSafe'])->name('orders.pessimistic.sync.safe.with-limit');
    });

    Route::prefix('orders/queue/pessimistic/with-limit')->middleware('throttle:orders-queue')->group(function () {
        Route::post('/unsafe', [OrderQueueController::class, 'storeAsyncUnsafe'])->name('orders.pessimistic.queue.unsafe.with-limit');
        Route::post('/safe', [OrderQueueController::class, 'storeAsyncSafe'])->name('orders.pessimistic.queue.safe.with-limit');
    });

    Route::prefix('orders/queue/pessimistic/no-limit')->group(function () {
        Route::post('/unsafe', [OrderQueueController::class, 'storeAsyncUnsafe'])->name('orders.pessimistic.queue.unsafe.no-limit');
        Route::post('/safe', [OrderQueueController::class, 'storeAsyncSafe'])->name('orders.pessimistic.queue.safe.no-limit');
    });

    Route::post('/orders/optimistic/test/no-limit/unsafe', [OrderController::class, 'storeSyncUnsafe'])->name('orders.optimistic.sync.unsafe.no-limit');
    Route::post('/orders/optimistic/test/no-limit/safe', [OrderController::class, 'storeSyncOptimisticSafe'])->name('orders.optimistic.sync.safe.no-limit');

    Route::middleware('throttle:orders')->group(function () {
        Route::post('/orders/optimistic/test/with-limit/unsafe', [OrderController::class, 'storeSyncUnsafe'])->name('orders.optimistic.sync.unsafe.with-limit');
        Route::post('/orders/optimistic/test/with-limit/safe', [OrderController::class, 'storeSyncOptimisticSafe'])->name('orders.optimistic.sync.safe.with-limit');
    });

    Route::prefix('orders/queue/optimistic/with-limit')->middleware('throttle:orders-queue')->group(function () {
        Route::post('/unsafe', [OrderQueueController::class, 'storeAsyncUnsafe'])->name('orders.optimistic.queue.unsafe.with-limit');
        Route::post('/safe', [OrderQueueController::class, 'storeAsyncOptimisticSafe'])->name('orders.optimistic.queue.safe.with-limit');
    });

    Route::prefix('orders/queue/optimistic/no-limit')->group(function () {
        Route::post('/unsafe', [OrderQueueController::class, 'storeAsyncUnsafe'])->name('orders.optimistic.queue.unsafe.no-limit');
        Route::post('/safe', [OrderQueueController::class, 'storeAsyncOptimisticSafe'])->name('orders.optimistic.queue.safe.no-limit');
    });

    Route::get('/orders', [OrderController::class, 'index'])->name('orders.list');
    Route::get('/orders/my', [OrderController::class, 'myOrders'])->name('orders.my');
    Route::get('/orders/{id}', [OrderController::class, 'show'])->name('orders.show');
    Route::put('/orders', [OrderController::class, 'update'])->name('orders.update');
    Route::delete('/orders', [OrderController::class, 'destroy'])->name('orders.delete');
    Route::post('/orders/cancel', [OrderController::class, 'cancel'])->name('orders.cancel');
    Route::post('/orders/test/transaction-failure', [OrderController::class, 'testTransactionFailure'])
        ->name('orders.test.transaction-failure');
        
    Route::prefix('cart')->group(function () {
        Route::get('/', [CartController::class, 'index'])->name('cart.view');
        Route::post('/add', [CartController::class, 'add'])->name('cart.add');
        Route::post('/remove', [CartController::class, 'remove'])->name('cart.remove');
        Route::put('/update', [CartController::class, 'update'])->name('cart.update');
        Route::post('/clear', [CartController::class, 'clear'])->name('cart.clear');
        Route::post('/checkout', [CartController::class, 'checkout'])->name('cart.checkout.pessimistic');
        Route::post('/checkout/optimistic', [CartController::class, 'checkoutOptimistic'])->name('cart.checkout.optimistic');
    });
});

Route::fallback(function () {
    return response()->json(['message' => 'Route Not Found'], 404);
})->name('fallback');
