<?php

namespace App\Services;

use App\Jobs\ProcessOrderJob;
use App\Jobs\ProcessOrderOptimisticJob;
use App\Repositories\Contracts\CartServiceInterface;
use App\Repositories\Contracts\ProductRepositoryInterface;
use Illuminate\Support\Facades\Redis;

class CartService implements CartServiceInterface
{
    private const CART_PREFIX = 'cart:user:';
    private const CART_TTL = 86400 * 7;

    public function __construct(
        private ProductRepositoryInterface $productRepo,
        private OrderService $orderService
    ) {}

    private function cartKey(int $userId): string
    {
        return self::CART_PREFIX . $userId;
    }

    public function getCart(int $userId): array
    {
        $items = Redis::hgetall($this->cartKey($userId));
        if (empty($items)) {
            return [];
        }
        return array_map(fn($json) => json_decode($json, true), $items);
    }

    public function addItem(int $userId, int $productId, int $quantity): void
    {
        $product = $this->productRepo->find($productId);
        if (!$product) {
            throw new \RuntimeException('Product not found', 404);
        }
        $key = $this->cartKey($userId);
        $existing = Redis::hget($key, (string) $productId);
        if ($existing) {
            $item = json_decode($existing, true);
            $newQty = $item['quantity'] + $quantity;
        } else {
            $item = [
                'product_id' => $product->id,
                'name'       => $product->name,
                'price'      => $product->price,
                'quantity'   => 0,
            ];
            $newQty = $quantity;
        }
        if ($product->stock < $newQty) {
            throw new \RuntimeException('Insufficient stock', 409);
        }
        $item['quantity'] = $newQty;
        $item['price']    = $product->price;
        Redis::hset($key, (string) $productId, json_encode($item));
        Redis::expire($key, self::CART_TTL);
    }

    public function removeItem(int $userId, int $productId): void
    {
        Redis::hdel($this->cartKey($userId), (string) $productId);
    }

    public function updateQuantity(int $userId, int $productId, int $quantity): void
    {
        if ($quantity === 0) {
            $this->removeItem($userId, $productId);
            return;
        }
        $product = $this->productRepo->find($productId);
        if (!$product) {
            throw new \RuntimeException('Product not found', 404);
        }
        if ($product->stock < $quantity) {
            throw new \RuntimeException('Insufficient stock', 409);
        }
        $key = $this->cartKey($userId);
        $item = [
            'product_id' => $product->id,
            'name'       => $product->name,
            'price'      => $product->price,
            'quantity'   => $quantity,
        ];
        Redis::hset($key, (string) $productId, json_encode($item));
    }

    public function clearCart(int $userId): void
    {
        Redis::del($this->cartKey($userId));
    }

    public function checkout(int $userId): array
    {
        $cart = $this->getCart($userId);
        if (empty($cart)) {
            throw new \RuntimeException('Cart is empty', 400);
        }

        $items = array_map(fn($item) => [
            'product_id' => $item['product_id'],
            'quantity'   => $item['quantity'],
        ], $cart);

        $order = $this->orderService->createPendingOrder($userId);

        ProcessOrderJob::dispatch(
            orderId: $order->id,
            items: $items,
            userId: $userId,
            mode: 'safe'
        )->onQueue('orders');

        $this->clearCart($userId);

        return [
            'order_id' => $order->id,
            'total'    => 0,
            'status'   => 'pending',
        ];
    }

    public function checkoutOptimistic(int $userId): array
    {
        $cart = $this->getCart($userId);
        if (empty($cart)) {
            throw new \RuntimeException('Cart is empty', 400);
        }

        $items = array_map(fn($item) => [
            'product_id' => $item['product_id'],
            'quantity'   => $item['quantity'],
        ], $cart);

        $order = $this->orderService->createPendingOrder($userId);

        ProcessOrderOptimisticJob::dispatch(
            orderId: $order->id,
            items: $items,
            userId: $userId,
            mode: 'safe'
        )->onQueue('orders');

        $this->clearCart($userId);

        return [
            'order_id' => $order->id,
            'total'    => 0,
            'status'   => 'pending',
        ];
    }
}
