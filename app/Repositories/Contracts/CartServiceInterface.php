<?php

namespace App\Repositories\Contracts;

interface CartServiceInterface
{
    public function getCart(int $userId): array;
    public function addItem(int $userId, int $productId, int $quantity): void;
    public function removeItem(int $userId, int $productId): void;
    public function updateQuantity(int $userId, int $productId, int $quantity): void;
    public function clearCart(int $userId): void;
    public function checkout(int $userId): array;
    public function checkoutOptimistic(int $userId): array;
}
